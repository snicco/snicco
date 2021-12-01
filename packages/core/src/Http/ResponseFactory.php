<?php

declare(strict_types=1);

namespace Snicco\Http;

use stdClass;
use JsonSerializable;
use Snicco\View\ViewEngine;
use InvalidArgumentException;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Redirector;
use Snicco\Contracts\Responsable;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\NullResponse;
use Snicco\View\Contracts\ViewInterface;
use Illuminate\Contracts\Support\Jsonable;
use Snicco\Http\Responses\RedirectResponse;
use Illuminate\Contracts\Support\Arrayable;
use Snicco\Http\Responses\DelegatedResponse;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

class ResponseFactory implements ResponseFactoryInterface, StreamFactoryInterface
{
    
    private ViewEngine $view_engine;
    
    private Psr17ResponseFactory $response_factory;
    
    private Psr17StreamFactory $stream_factory;
    
    private Redirector $redirector;
    
    private string $unrecoverable_error_message = 'Something has gone completely wrong.';
    
    public function __construct(ViewEngine $view, Psr17ResponseFactory $response, Psr17StreamFactory $stream, Redirector $redirector)
    {
        $this->view_engine = $view;
        $this->response_factory = $response;
        $this->stream_factory = $stream;
        $this->redirector = $redirector;
    }
    
    public function view(string $view, array $data = [], $status = 200, array $headers = []) :Response
    {
        $content = $this->view_engine->make($view)->with($data)->toString();
        $response = $this->html($content, $status);
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
    
    public function html(string $html, int $status_code = 200) :Response
    {
        return $this->make($status_code)
                    ->html($this->stream_factory->createStream($html));
    }
    
    public function make(int $status_code = 200, $reason_phrase = '') :Response
    {
        if ( ! $this->isValidStatus($status_code)) {
            throw new InvalidArgumentException(
                "The HTTP status code [$status_code] is not valid."
            );
        }
        
        $psr_response = $this->response_factory->createResponse($status_code, $reason_phrase);
        
        return new Response($psr_response);
    }
    
    public function createResponse(int $code = 200, string $reasonPhrase = '') :Response
    {
        return $this->make($code, $reasonPhrase);
    }
    
    public function createStream(string $content = '') :StreamInterface
    {
        return $this->stream_factory->createStream($content);
    }
    
    public function null() :NullResponse
    {
        return new NullResponse($this->response_factory->createResponse());
    }
    
    public function delegateToWP() :DelegatedResponse
    {
        return new DelegatedResponse($this->createResponse());
    }
    
    public function redirectToRoute(string $route, $args = [], int $status = 302, $secure = true, $absolute = false) :RedirectResponse
    {
        return $this->redirect()->toRoute($route, $status, $args, $secure, $absolute);
    }
    
    public function redirect() :Redirector
    {
        return $this->redirector;
    }
    
    public function back(string $fallback = '/', int $status = 302) :RedirectResponse
    {
        return $this->redirect()->back($status, $fallback);
    }
    
    /**
     * @note no formatting is performed on the path.
     */
    public function permanentRedirectTo(string $path) :RedirectResponse
    {
        return $this->redirector->createRedirectResponse($path, 301);
    }
    
    public function redirectToLogin(bool $reauth = false, string $redirect_on_login = '', int $status_code = 302) :RedirectResponse
    {
        return $this->redirector->toLogin($redirect_on_login, $reauth, $status_code);
    }
    
    public function signedLogout(int $user_id, string $redirect_on_logout = '/', $status = 302, int $expiration = 3600) :RedirectResponse
    {
        return $this->redirector->signedLogout($user_id, $redirect_on_logout, $status, $expiration);
    }
    
    public function noContent() :Response
    {
        return $this->make(204);
    }
    
    public function createStreamFromFile(string $filename, string $mode = 'r') :StreamInterface
    {
        return $this->stream_factory->createStreamFromFile($filename, $mode);
    }
    
    public function createStreamFromResource($resource) :StreamInterface
    {
        return $this->stream_factory->createStreamFromResource($resource);
    }
    
    public function setFallbackErrorMessage(string $message)
    {
        $this->unrecoverable_error_message = $message;
    }
    
    /**
     * @throws HttpException
     */
    public function toResponse($response) :Response
    {
        if ($response instanceof Response) {
            return $response;
        }
        
        if ($response instanceof ResponseInterface) {
            return new Response($response);
        }
        
        if ($response instanceof ViewInterface) {
            return $this->toResponse($response->toString());
        }
        
        if (is_string($response)) {
            return $this->html($response);
        }
        
        if (is_array($response) || $response instanceof JsonSerializable
            || $response
               instanceof
               stdClass) {
            return $this->json($response);
        }
        
        if ($response instanceof Arrayable) {
            return $this->json($response->toArray());
        }
        
        if ($response instanceof Jsonable) {
            $stream = $this->createStream($response->toJson());
            return $this->make(200)->json($stream);
        }
        
        if ($response instanceof Responsable) {
            return $this->toResponse(
                $response->toResponsable()
            );
        }
        
        throw new HttpException(500, "Invalid response returned by a route.");
    }
    
    public function json($content, int $status = 200) :Response
    {
        /** @todo This needs more parsing or a dedicated JsonResponseClass. See symfony/illuminate */
        return $this->make($status)
                    ->json($this->createStream(json_encode($content)));
    }
    
    private function isValidStatus(int $status_code) :bool
    {
        return 100 <= $status_code && $status_code < 600;
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Http;

use Throwable;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\NullResponse;
use Snicco\Contracts\AbstractRedirector;
use Snicco\Http\Responses\InvalidResponse;
use Snicco\Contracts\ResponseableInterface;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Http\Responses\DelegatedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\Exceptions\ViewException;
use Snicco\Contracts\ViewFactoryInterface as ViewFactory;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

/**
 * @todo either this class or the Response class need a prepare method to fix obvious mistakes.
 * See implementation in Symfony Response.
 */
class ResponseFactory implements ResponseFactoryInterface
{
    
    private ViewFactory          $view_factory;
    private Psr17ResponseFactory $response_factory;
    private Psr17StreamFactory   $stream_factory;
    private AbstractRedirector   $redirector;
    private string               $unrecoverable_error_message = 'Something has gone completely wrong.';
    
    public function __construct(ViewFactory $view, Psr17ResponseFactory $response, Psr17StreamFactory $stream, AbstractRedirector $redirector)
    {
        
        $this->view_factory = $view;
        $this->response_factory = $response;
        $this->stream_factory = $stream;
        
        $this->redirector = $redirector;
    }
    
    public function view(string $view, array $data = [], $status = 200, array $headers = []) :Response
    {
        
        $content = $this->view_factory->make($view)->with($data)->toString();
        
        $psr_response = $this->make($status)
                             ->html($this->stream_factory->createStream($content));
        
        $response = new Response($psr_response);
        
        foreach ($headers as $name => $value) {
            
            $response = $response->withHeader($name, $value);
            
        }
        
        return $response;
        
    }
    
    public function make(int $status_code = 200, $reason_phrase = '') :Response
    {
        
        $psr_response = $this->response_factory->createResponse($status_code, $reason_phrase);
        
        return new Response($psr_response);
        
    }
    
    /**
     * @return Response
     */
    public function createResponse(int $code = 200, string $reasonPhrase = '') :ResponseInterface
    {
        return $this->make($code, $reasonPhrase);
    }
    
    public function null() :NullResponse
    {
        return new NullResponse($this->response_factory->createResponse(204));
    }
    
    public function delegateToWP() :DelegatedResponse
    {
        return new DelegatedResponse($this->createResponse());
    }
    
    public function redirectToRoute(string $route, $args = [], int $status = 302, $secure = true, $absolute = false) :RedirectResponse
    {
        return $this->redirect()->toRoute($route, $status, $args, $secure, $absolute);
    }
    
    public function redirect() :AbstractRedirector
    {
        return $this->redirector;
    }
    
    public function back(string $fallback = '/', int $status = 302, bool $external_referer = false) :RedirectResponse
    {
        
        return $this->redirect()->back($status, $fallback, $external_referer);
        
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
     * Render the most appropriate error view for the given Exception.
     * This method DOES ONLY RETURN text/html responses.
     *
     * @access internal.
     */
    public function error(HttpException $e, Request $request) :Response
    {
        
        $views = [(string) $e->httpStatusCode(), 'error', 'index'];
        
        $is_admin = $request->isWpAdmin();
        
        if ($is_admin) {
            
            $views = collect($views)
                ->map(fn($view) => $view.'-admin')
                ->merge($views)->all();
            
        }
        
        try {
            
            $view = $this->view_factory->make($views)->with([
                'status_code' => $e->httpStatusCode(),
                'message' => $e->messageForUsers(),
            ]);
            
            return $this->toResponse($view)
                        ->withStatus($e->httpStatusCode());
            
        } catch (ViewException $e) {
            
            $view = $is_admin ? 'error-admin' : 'error';
            
            try {
                
                return $this->toResponse(
                    
                    $this->view_factory
                        ->make($view)
                        ->with([
                            'status_code' => 500,
                            'message' => $this->unrecoverable_error_message,
                        ])
                
                )->withStatus(500);
            } catch (Throwable $e) {
                
                return $this->html("<h1>$this->unrecoverable_error_message</h1>", 500);
                
            }
            
        }
        
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
        
        if (is_string($response)) {
            
            return $this->html($response);
            
        }
        
        if (is_array($response)) {
            
            return $this->json($response);
            
        }
        
        if ($response instanceof ResponseableInterface) {
            
            return $this->toResponse(
                $response->toResponsable()
            );
            
        }
        
        throw new HttpException(500, "Invalid response returned by a route.");
        
    }
    
    public function html(string $html, int $status_code = 200) :Response
    {
        
        return $this->make($status_code)
                    ->html($this->stream_factory->createStream($html));
        
    }
    
    public function json($content, int $status = 200) :Response
    {
        
        /** @todo This needs more parsing or a dedicated JsonResponseClass */
        return $this->make($status)
                    ->json(
                        $this->createStream(json_encode($content))
                    );
        
    }
    
    public function createStream(string $content = '') :StreamInterface
    {
        
        return $this->stream_factory->createStream($content);
    }
    
    public function invalidResponse() :InvalidResponse
    {
        return new InvalidResponse($this->response_factory->createResponse(500));
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use stdClass;
use JsonSerializable;
use InvalidArgumentException;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Contracts\Responsable;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Psr\Http\Message\StreamInterface as Psr7Stream;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

/**
 * @interal You should never depend on this concrete response factory implementation.
 * Depend on the interface always.
 */
class BaseResponseFactory implements ResponseFactory
{
    
    /**
     * @var Psr17ResponseFactory
     */
    private $response_factory;
    
    /**
     * @var Psr17StreamFactory
     */
    private $stream_factory;
    
    /**
     * @var Redirector
     */
    private $redirector;
    
    public function __construct(Psr17ResponseFactory $response, Psr17StreamFactory $stream, Redirector $redirector)
    {
        $this->response_factory = $response;
        $this->stream_factory = $stream;
        $this->redirector = $redirector;
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
    
    public function createResponse(int $code = 200, string $reasonPhrase = '') :Psr7Response
    {
        return $this->make($code, $reasonPhrase);
    }
    
    public function createStream(string $content = '') :Psr7Stream
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
    
    public function redirect(string $path = null, int $status_code = 302)
    {
        if (is_null($path)) {
            return $this->redirector;
        }
        
        return $this->redirector->to($path, $status_code);
    }
    
    public function noContent() :Response
    {
        return $this->make(204);
    }
    
    public function createStreamFromFile(string $filename, string $mode = 'r') :Psr7Stream
    {
        return $this->stream_factory->createStreamFromFile($filename, $mode);
    }
    
    public function createStreamFromResource($resource) :Psr7Stream
    {
        return $this->stream_factory->createStreamFromResource($resource);
    }
    
    public function toResponse($response) :Response
    {
        if ($response instanceof Response) {
            return $response;
        }
        
        if ($response instanceof Psr7Response) {
            return new Response($response);
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
        
        if ($response instanceof Responsable) {
            return $this->toResponse(
                $response->toResponsable()
            );
        }
        
        throw new InvalidArgumentException('Invalid response returned by a route.');
    }
    
    public function json($content, int $status_code = 200) :Response
    {
        /** @todo This needs more parsing or a dedicated JsonResponseClass. See symfony/illuminate */
        return $this->make($status_code)
                    ->json($this->createStream(json_encode($content)));
    }
    
    private function isValidStatus(int $status_code) :bool
    {
        return 100 <= $status_code && $status_code < 600;
    }
    
}
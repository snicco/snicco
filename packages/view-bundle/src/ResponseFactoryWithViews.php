<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\ResponseFactory;
use Psr\Http\Message\StreamInterface;
use Snicco\Http\Responses\NullResponse;
use Snicco\Http\Responses\DelegatedResponse;
use Psr\Http\Message\ResponseInterface as Psr7Response;

/**
 * @interal
 */
final class ResponseFactoryWithViews implements ViewResponseFactory
{
    
    /**
     * @var ResponseFactory
     */
    private $response_factory;
    
    /**
     * @var ViewEngine
     */
    private $view_engine;
    
    public function __construct(ViewEngine $view_engine, ResponseFactory $response_factory)
    {
        $this->view_engine = $view_engine;
        $this->response_factory = $response_factory;
    }
    
    public function view(string $view, array $data = [], $status = 200, array $headers = []) :Response
    {
        $response = $this->html($this->getHtml($view, $data), $status);
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
    
    public function toResponse($response) :Response
    {
        return $this->response_factory->toResponse($response);
    }
    
    public function make(int $status_code = 200, string $reason_phrase = '') :Response
    {
        return $this->response_factory->make($status_code, $reason_phrase);
    }
    
    public function html(string $html, int $status_code = 200) :Response
    {
        return $this->response_factory->html($html, $status_code);
    }
    
    public function json($content, int $status_code = 200) :Response
    {
        return $this->response_factory->json($content, $status_code);
    }
    
    public function redirect(string $path = null, int $status_code = 302)
    {
        return $this->response_factory->redirect($path, $status_code);
    }
    
    public function null() :NullResponse
    {
        return $this->response_factory->null();
    }
    
    public function noContent() :Response
    {
        return $this->response_factory->noContent();
    }
    
    public function delegateToWP() :DelegatedResponse
    {
        return $this->response_factory->delegateToWP();
    }
    
    public function createResponse(int $code = 200, string $reasonPhrase = '') :Psr7Response
    {
        return $this->response_factory->createResponse($code, $reasonPhrase);
    }
    
    public function createStream(string $content = '') :StreamInterface
    {
        return $this->response_factory->createStream($content);
    }
    
    public function createStreamFromFile(string $filename, string $mode = 'r') :StreamInterface
    {
        return $this->response_factory->createStreamFromFile($filename, $mode);
    }
    
    public function createStreamFromResource($resource) :StreamInterface
    {
        return $this->response_factory->createStreamFromResource($resource);
    }
    
    public function getHtml(string $template_name, array $data = []) :string
    {
        return $this->view_engine->make($template_name)->with($data)->toString();
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use stdClass;
use JsonSerializable;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Http\Responses\RedirectResponse;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

interface ResponseFactory extends Psr17ResponseFactory, Psr17StreamFactory
{
    
    /**
     * @param  string|array|Response|Psr7Response|stdClass|JsonSerializable|Responsable  $response
     */
    public function toResponse($response) :Response;
    
    public function make(int $status_code = 200, string $reason_phrase = '') :Response;
    
    public function html(string $html, int $status_code = 200) :Response;
    
    /**
     * @param  mixed  $content  Anything that can be passed to {@see json_encode()}
     */
    public function json($content, int $status_code = 200) :Response;
    
    public function redirect(string $location, int $status_code = 302) :RedirectResponse;
    
    public function noContent() :Response;
    
    public function delegate(bool $should_headers_be_sent = true) :DelegatedResponse;
    
}
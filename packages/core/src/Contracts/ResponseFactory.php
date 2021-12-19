<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use stdClass;
use JsonSerializable;
use InvalidArgumentException;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Core\Http\Responses\RedirectResponse;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;

interface ResponseFactory extends Psr17ResponseFactory, Psr17StreamFactory
{
    
    /**
     * @param  string|array|Response|Psr7Response|stdClass|JsonSerializable|Responsable  $response
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    public function toResponse($response) :Response;
    
    /**
     * @throws InvalidArgumentException When an invalid http status code is passed.
     */
    public function make(int $status_code = 200, string $reason_phrase = '') :Response;
    
    /**
     * @throws InvalidArgumentException When an invalid http status code is passed.
     */
    public function html(string $html, int $status_code = 200) :Response;
    
    /**
     * @param  mixed  $content  Anything that can be passed to {@see json_encode()}
     * @param  int  $status_code
     *
     * @return Response
     * @throws InvalidArgumentException When an invalid http status code is passed.
     */
    public function json($content, int $status_code = 200) :Response;
    
    /**
     * @param  string|null  $path
     * @param  int  $status_code
     *
     * @return Redirector|RedirectResponse Returns a redirector instance if path is null
     */
    public function redirect(string $path = null, int $status_code = 302);
    
    public function null() :NullResponse;
    
    /**
     * Returns a no content response (204 status code)
     *
     * @return Response
     */
    public function noContent() :Response;
    
    public function delegateToWP() :DelegatedResponse;
    
}
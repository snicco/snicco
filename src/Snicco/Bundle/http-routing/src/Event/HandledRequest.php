<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Event;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @psalm-immutable
 */
final class HandledRequest
{
    public Request $request;
    public Response $response;
    public float $time;

    public function __construct(Request $request, Response $response, float $time)
    {
        $this->request = $request;
        $this->response = $response;
        $this->time = $time;
    }
}
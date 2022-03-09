<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Event;

use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @psalm-immutable
 */
final class ResponseSent
{
    public Response $response;

    public bool $body_sent;

    public function __construct(Response $response, bool $body_sent)
    {
        $this->response = $response;
        $this->body_sent = $body_sent;
    }
}

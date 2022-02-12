<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;

final class BooleanMiddleware extends Middleware
{

    private string $val;

    public function __construct(bool $val)
    {
        $this->val = 'boolean_' . ($val ? 'true' : 'false');
    }

    public function handle(Request $request, $next): ResponseInterface
    {
        $response = $next($request);

        $response->getBody()->write(':' . $this->val);
        return $response;
    }

}
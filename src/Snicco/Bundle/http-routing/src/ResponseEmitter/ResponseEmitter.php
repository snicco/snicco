<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ResponseEmitter;

use Snicco\Component\HttpRouting\Http\Psr7\Response;

interface ResponseEmitter
{
    public function emit(Response $response): void;
}
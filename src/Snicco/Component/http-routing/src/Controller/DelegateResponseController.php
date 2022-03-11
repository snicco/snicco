<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;

/**
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class DelegateResponseController extends Controller
{
    public function __invoke(): DelegatedResponse
    {
        return $this->responseFactory()
            ->delegate(true);
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use stdClass;

/**
 * @codeCoverageIgnore
 */
interface Responsable
{
    /**
     * Convert an object to a something type that can be processed be the
     * response factory.
     *
     * @return array|JsonSerializable|Psr7Response|Responsable|Response|stdClass|string
     *
     * @see ResponseFactory::toResponse()
     */
    public function toResponsable();
}

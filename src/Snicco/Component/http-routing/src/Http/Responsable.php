<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;

interface Responsable
{
    
    /**
     * Convert an object to a something type
     * that can be processed be the response factory
     *
     * @return mixed
     * @see ResponseFactory::toResponse()
     */
    public function toResponsable();
    
}

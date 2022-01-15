<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

interface Responsable
{
    
    /**
     * Convert an object to a data type
     * that can be processed be the response factory
     *
     * @return mixed
     * @see HttpResponseFactory::toResponse()
     */
    public function toResponsable();
    
}

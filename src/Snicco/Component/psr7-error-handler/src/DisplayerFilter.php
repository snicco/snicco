<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

/**
 * @api
 */
interface DisplayerFilter
{
    
    /**
     * @param  Displayer[]  $displayers
     *
     * @return Displayer[]
     */
    public function filter(array $displayers, RequestInterface $request, ExceptionInformation $info) :array;
    
}
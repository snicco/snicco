<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

use Psr\Http\Message\RequestInterface;

interface DisplayerFilter
{
    
    /**
     * @param  Displayer[]  $displayers
     *
     * @return Displayer[]
     */
    public function filter(array $displayers, RequestInterface $request, HttpException $e) :array;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\Core\ExceptionHandling;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Core\ExceptionHandling\Exceptions\HttpException;

interface HtmlErrorRender
{
    
    public function render(HttpException $e, Request $request) :string;
    
}
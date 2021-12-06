<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Snicco\Http\Psr7\Request;
use Snicco\ExceptionHandling\Exceptions\HttpException;

interface HtmlErrorRender
{
    
    public function render(HttpException $e, Request $request) :string;
    
}
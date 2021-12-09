<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;

interface HtmlErrorRender
{
    
    public function render(HttpException $e, Request $request) :string;
    
}
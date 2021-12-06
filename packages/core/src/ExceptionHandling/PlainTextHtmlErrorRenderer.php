<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Snicco\Http\Psr7\Request;
use Snicco\ExceptionHandling\Exceptions\HttpException;

/** @todo tests */
final class PlainTextHtmlErrorRenderer implements HtmlErrorRender
{
    
    public function render(HttpException $e, Request $request) :string
    {
        return "<h1>{$e->messageForUsers()}</h1>";
    }
    
}
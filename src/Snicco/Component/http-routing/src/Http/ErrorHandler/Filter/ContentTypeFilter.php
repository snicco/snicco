<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler\Filter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Displayer;
use Snicco\Component\HttpRouting\Http\ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\ErrorHandler\DisplayerFilter;

use function array_filter;

final class ContentTypeFilter implements DisplayerFilter
{
    
    public function filter(array $displayers, RequestInterface $request, HttpException $e) :array
    {
        return array_filter($displayers, function (Displayer $displayer) use ($request) {
            return $this->matchingContentTypes($request, $displayer);
        });
    }
    
    private function matchingContentTypes(RequestInterface $request, Displayer $displayer) :bool
    {
        $accept = $request->getHeaderLine('Accept');
        return $accept === $displayer->supportedContentType();
    }
    
}
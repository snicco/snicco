<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler\Filter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Displayer;
use Snicco\Component\HttpRouting\Http\ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\ErrorHandler\DisplayerFilter;

use function array_filter;

final class VerbosityFilter implements DisplayerFilter
{
    
    private bool $show_verbose_filters;
    
    public function __construct(bool $show_verbose_filters)
    {
        $this->show_verbose_filters = $show_verbose_filters;
    }
    
    public function filter(array $displayers, RequestInterface $request, HttpException $e) :array
    {
        return array_filter($displayers, function (Displayer $d) {
            if (false === $this->show_verbose_filters) {
                return false === $d->isVerbose();
            }
            return true;
        }
        );
    }
    
}
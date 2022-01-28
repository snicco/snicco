<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Filter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\Psr7ErrorHandler\Displayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_filter;

/**
 * @api
 */
final class VerbosityFilter implements DisplayerFilter
{
    
    private bool $show_verbose_filters;
    
    public function __construct(bool $show_verbose_filters)
    {
        $this->show_verbose_filters = $show_verbose_filters;
    }
    
    public function filter(array $displayers, RequestInterface $request, ExceptionInformation $info) :array
    {
        return array_filter($displayers, function (Displayer $d) {
            return $this->show_verbose_filters || ! $d->isVerbose();
        });
    }
    
}
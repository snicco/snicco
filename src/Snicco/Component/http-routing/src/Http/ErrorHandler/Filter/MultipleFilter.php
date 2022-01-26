<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler\Filter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\HttpRouting\Http\ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\ErrorHandler\DisplayerFilter;

use function array_map;

final class MultipleFilter implements DisplayerFilter
{
    
    /**
     * @var DisplayerFilter[]
     */
    private array $filters;
    
    public function __construct(DisplayerFilter ...$filter)
    {
        $this->filters = array_map(fn(DisplayerFilter $filter) => $filter, $filter);
    }
    
    public function filter(array $displayers, RequestInterface $request, HttpException $e) :array
    {
        foreach ($this->filters as $filter) {
            $displayers = $filter->filter($displayers, $request, $e);
        }
        return $displayers;
    }
    
}
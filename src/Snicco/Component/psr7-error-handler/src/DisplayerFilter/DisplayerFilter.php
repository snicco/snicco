<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\DisplayerFilter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

interface DisplayerFilter
{

    /**
     * @param ExceptionDisplayer[] $displayers
     *
     * @return ExceptionDisplayer[]
     */
    public function filter(array $displayers, RequestInterface $request, ExceptionInformation $info): array;

}
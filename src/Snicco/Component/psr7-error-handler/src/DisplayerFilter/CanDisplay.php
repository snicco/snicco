<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\DisplayerFilter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_filter;

final class CanDisplay implements DisplayerFilter
{
    public function filter(array $displayers, RequestInterface $request, ExceptionInformation $info): array
    {
        return array_filter($displayers, fn (ExceptionDisplayer $displayer) => $displayer->canDisplay($info));
    }
}

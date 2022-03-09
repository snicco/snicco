<?php

declare(strict_types=1);

namespace Snicco\Bundle\Debug\Displayer;

use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Whoops\Run;

final class WhoopsHtmlDisplayer implements ExceptionDisplayer
{
    private Run $whoops;

    public function __construct(Run $whoops)
    {
        $this->whoops = $whoops;
    }

    public function display(ExceptionInformation $exception_information): string
    {
        return $this->whoops->handleException($exception_information->originalException());
    }

    public function supportedContentType(): string
    {
        return 'text/html';
    }

    public function isVerbose(): bool
    {
        return true;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }
}

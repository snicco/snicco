<?php

declare(strict_types=1);

namespace Snicco\Bundle\Debug\Tests\fixtures;

use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

final class StubDisplayer implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return 'stub';
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

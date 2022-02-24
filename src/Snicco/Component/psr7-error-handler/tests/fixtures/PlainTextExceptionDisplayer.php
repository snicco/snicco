<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

final class PlainTextExceptionDisplayer implements ExceptionDisplayer
{

    private bool $should_handle;

    public function __construct(bool $should_handle = true)
    {
        $this->should_handle = $should_handle;
    }

    public function display(ExceptionInformation $exception_information): string
    {
        return 'plain_text1:title:'
            . $exception_information->safeTitle()
            . ':id:'
            . $exception_information->identifier()
            . ':details:'
            . $exception_information->safeDetails();
    }

    public function supportedContentType(): string
    {
        return 'text/plain';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return $this->should_handle;
    }

}
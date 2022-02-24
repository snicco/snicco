<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function json_encode;

final class JsonExceptionDisplayer implements ExceptionDisplayer
{

    public function display(ExceptionInformation $exception_information): string
    {
        return json_encode([
            'title' => $exception_information->safeTitle(),
            'details' => $exception_information->safeDetails(),
            'identifier' => $exception_information->identifier(),
        ]);
    }

    public function supportedContentType(): string
    {
        return 'application/json';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }

}
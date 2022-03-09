<?php

declare(strict_types=1);


namespace Snicco\Component\Psr7ErrorHandler\Displayer;

use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class FallbackJsonDisplayer implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return json_encode([
            'errors' => [
                [
                    'identifier' => $exception_information->identifier(),
                    'title' => $exception_information->safeTitle(),
                    'details' => $exception_information->safeDetails(),
                ]
            ]
        ], JSON_THROW_ON_ERROR);
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

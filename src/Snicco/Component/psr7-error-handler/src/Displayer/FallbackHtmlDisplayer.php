<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Displayer;

use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function dirname;
use function htmlentities;
use function str_replace;

use const ENT_QUOTES;

final class FallbackHtmlDisplayer implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        $content = @file_get_contents($file = dirname(__DIR__, 2) . '/resources/error.fallback.html');

        if (false === $content) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Cant read fallback error template at location [{$file}].");
            // @codeCoverageIgnoreEnd
        }

        $content = str_replace('{{title}}', htmlentities($exception_information->safeTitle(), ENT_QUOTES), $content);
        $content = str_replace(
            '{{details}}',
            htmlentities($exception_information->safeDetails(), ENT_QUOTES),
            $content
        );
        $content = str_replace(
            '{{identifier}}',
            htmlentities($exception_information->identifier(), ENT_QUOTES),
            $content
        );

        return str_replace(
            '{{status}}',
            htmlentities((string) $exception_information->statusCode(), ENT_QUOTES),
            $content
        );
    }

    public function supportedContentType(): string
    {
        return 'text/html';
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

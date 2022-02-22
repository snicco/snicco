<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Displayer;

use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

final class FallbackDisplayer implements ExceptionDisplayer
{

    public function display(ExceptionInformation $exception_information): string
    {
        $code = sprintf(
            'This error can be identified by the code <b>[%s]</b>',
            htmlentities($exception_information->identifier(), ENT_QUOTES, 'UTF-8')
        );

        $title = $exception_information->safeTitle();
        $details = $exception_information->safeDetails();

        return sprintf(
            '<h1>%s</h1><p>%s</p><p>%s</p><p>%s</p>',
            $title,
            $details,
            $code,
            'We are sorry for any inconvenience caused.'
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
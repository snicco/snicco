<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures;

use Snicco\Component\BetterWPMail\Renderer\MailRenderer;

class NamedViewRenderer implements MailRenderer
{

    public function getMailContent(string $template_name, array $context = []): string
    {
        return $template_name;
    }

    public function supports(string $template_name, ?string $extension = null): bool
    {
        return $extension === 'php' || strpos($template_name, '.') !== false;
    }

}
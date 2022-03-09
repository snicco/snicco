<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures;

use Snicco\Component\BetterWPMail\Renderer\MailRenderer;

class NamedViewRenderer implements MailRenderer
{
    public function render(string $template_name, array $context = []): string
    {
        return $template_name;
    }

    public function supports(string $template_name, ?string $extension = null): bool
    {
        return 'php' === $extension || false !== strpos($template_name, '.');
    }
}

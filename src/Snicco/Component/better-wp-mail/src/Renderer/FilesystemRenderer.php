<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Renderer;

use function count;

final class FilesystemRenderer implements MailRenderer
{
    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function render(string $template_name, array $context = []): string
    {
        ob_start();
        (static function () use ($template_name, $context) {
            extract($context, EXTR_SKIP);
            require $template_name;
        })();

        return ob_get_clean() ?: '';
    }

    public function supports(string $template_name, ?string $extension = null): bool
    {
        if (empty($extension)) {
            return false;
        }
        $intersect = array_intersect([$extension], ['txt', 'php', 'html']);

        return count($intersect) > 0;
    }
}

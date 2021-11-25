<?php

declare(strict_types=1);

namespace Snicco\Mail\Renderer;

use Snicco\Mail\Contracts\MailRenderer;

/**
 * @internal
 */
final class FilesystemRenderer implements MailRenderer
{
    
    /**
     * @param  string  $template_name
     * @param  array  $context
     *
     * @return string|resource
     */
    public function getMailContent(string $template_name, array $context = []) :string
    {
        ob_start();
        (static function () use ($template_name, $context) {
            extract($context, EXTR_SKIP);
            require $template_name;
        })();
        return ob_get_clean();
    }
    
    /**
     * @param  string  $view
     * @param  string|null  $extension
     *
     * @return bool
     */
    public function supports(string $view, ?string $extension = null) :bool
    {
        if (empty($extension)) {
            return false;
        }
        $intersect = array_intersect([$extension], ['txt', 'php', 'html']);
        return count($intersect) > 0;
    }
    
}
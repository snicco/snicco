<?php

declare(strict_types=1);

namespace Snicco\Mail\Implementations;

use Snicco\Mail\Contracts\MailRenderer;

/**
 * @internal
 */
final class FilesystemRenderer implements MailRenderer
{
    
    /**
     * @param  string  $view
     * @param  array  $context
     *
     * @return string
     */
    public function getMailContent(string $view, array $context = []) :string
    {
        ob_start();
        (static function () use ($view, $context) {
            extract($context, EXTR_SKIP);
            require $view;
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
<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\Core\Contracts\TemplateRenderer;

final class FileBasedHtmlResponseFactory implements TemplateRenderer
{
    
    public function render(string $template_name, array $data = []) :string
    {
        ob_start();
        (static function () use ($template_name, $data) {
            extract($data, EXTR_SKIP);
            require $template_name;
        })();
        return ob_get_clean();
    }
    
}
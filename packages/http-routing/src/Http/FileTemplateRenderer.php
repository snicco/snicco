<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Http;

final class FileTemplateRenderer implements TemplateRenderer
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
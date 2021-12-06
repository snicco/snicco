<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Contracts\CreatesHtmlResponse;

final class FileBasedHtmlResponse implements CreatesHtmlResponse
{
    
    public function getHtml(string $template_name, array $data = []) :string
    {
        ob_start();
        (static function () use ($template_name, $data) {
            extract($data, EXTR_SKIP);
            require $template_name;
        })();
        return ob_get_clean();
    }
    
}
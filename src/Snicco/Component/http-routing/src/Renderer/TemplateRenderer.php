<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Renderer;

use Snicco\Component\HttpRouting\Exception\CouldNotRenderTemplate;

interface TemplateRenderer
{

    /**
     * @throws CouldNotRenderTemplate
     */
    public function render(string $template_name, array $data = []): string;

}
<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

interface TemplateRenderer
{
    
    public function render(string $template_name, array $data = []) :string;
    
}
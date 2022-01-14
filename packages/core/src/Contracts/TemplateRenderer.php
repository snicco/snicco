<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

interface TemplateRenderer
{
    
    /**
     * @todo add dedicated exception class for the interface
     */
    public function render(string $template_name, array $data = []) :string;
    
}
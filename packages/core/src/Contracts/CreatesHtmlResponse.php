<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface CreatesHtmlResponse
{
    
    public function getHtml(string $template_name, array $data = []) :string;
    
}
<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\View\ViewEngine;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;

/**
 * @interal
 */
final class ViewEngineTemplateRenderer implements TemplateRenderer
{
    
    /**
     * @var ViewEngine
     */
    private $view_engine;
    
    public function __construct(ViewEngine $view_engine)
    {
        $this->view_engine = $view_engine;
    }
    
    public function render(string $template_name, array $data = []) :string
    {
        return $this->view_engine->render($template_name, $data);
    }
    
}
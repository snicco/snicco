<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\TestDoubles;

use Snicco\View\Contracts\ViewFactory;
use Snicco\View\Contracts\ViewInterface;

class TestViewFactory implements ViewFactory
{
    
    private ?ViewInterface $rendered_view = null;
    
    public function make($views) :ViewInterface
    {
        $view = is_array($views) ? $views[0] : $views;
        
        $view = new TestView($view);
        
        if ( ! isset($this->rendered_view)) {
            $this->rendered_view = $view;
        }
        
        return $view;
    }
    
    public function render($views, array $context = []) :string
    {
        $view = $this->make($views);
        $view->with($context);
        return $view->toString();
    }
    
    public function renderedView() :?ViewInterface
    {
        return $this->rendered_view;
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\stubs;

use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\ViewFactoryInterface;

class TestViewFactory implements ViewFactoryInterface
{
    
    private ?ViewInterface $rendered_view = null;
    
    public function compose(ViewInterface $view)
    {
        //
    }
    
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
<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Support\Arr;
use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\ViewEngineInterface;
use Snicco\Contracts\ViewFactoryInterface;

class ViewFactory implements ViewFactoryInterface
{
    
    private ViewEngineInterface    $engine;
    private ViewComposerCollection $composer_collection;
    private GlobalContext          $global_context;
    private ?ViewInterface         $rendered_view;
    
    public function __construct(ViewEngineInterface $engine, ViewComposerCollection $composer_collection, GlobalContext $global_context)
    {
        
        $this->engine = $engine;
        $this->composer_collection = $composer_collection;
        $this->global_context = $global_context;
        
    }
    
    /**
     * Composes a view instance with contexts in the following order: Global, Composers, Local.
     *
     * @param  ViewInterface  $view
     *
     * @return void
     */
    public function compose(ViewInterface $view)
    {
        
        $local_context = $view->context();
        
        foreach ($this->global_context->get() as $name => $context) {
            
            $view->with($name, $context);
            
        }
        
        $this->composer_collection->executeUsing($view);
        
        $view->with($local_context);
        
        if ( ! isset($this->rendered_view)) {
            $this->rendered_view = $view;
        }
        
    }
    
    /**
     * Compile a view to a string.
     *
     * @param  string|string[]  $views
     * @param  array<string, mixed>  $context
     *
     * @return string
     */
    public function render($views, array $context = []) :string
    {
        
        $view = $this->make($views)->with($context);
        
        return $view->toString();
        
    }
    
    /**
     * Create a view instance.
     *
     * @param  string|string[]  $views
     *
     * @return ViewInterface
     */
    public function make($views) :ViewInterface
    {
        return $this->engine->make(Arr::wrap($views));
    }
    
    public function renderedView() :?ViewInterface
    {
        return $this->rendered_view ?? null;
    }
    
}

<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\View\Contracts\ViewFactory;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewNotFoundException;

/**
 * @api This class is a facade to the underlying ViewFactories.
 */
final class ViewEngine
{
    
    /**
     * @var ViewFactory[]
     */
    private $view_factories;
    
    /**
     * The root view that was rendered.
     *
     * @var ViewInterface|null
     */
    private $rendered_view;
    
    public function __construct(ViewFactory ...$view_factories)
    {
        $this->view_factories = $view_factories;
    }
    
    /**
     * Returns the view context as a string.
     *
     * @param  string|string[]  $views
     * @param  array<string, mixed>  $context
     *
     * @return string
     * @throws ViewNotFoundException
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
     * @throws ViewNotFoundException When no view can be created with any view factory
     */
    public function make($views) :ViewInterface
    {
        $views = (array) $views;
        
        $view = $this->getView($views);
        
        if ( ! isset($this->rendered_view)) {
            $this->rendered_view = $view;
        }
        
        return $view->with('__view', $this);
    }
    
    /**
     * The root view that was rendered.
     *
     * @return ViewInterface|null
     */
    public function rootView() :?ViewInterface
    {
        return $this->rendered_view ?? null;
    }
    
    private function getView(array $views) :ViewInterface
    {
        foreach ($this->view_factories as $view_factory) {
            try {
                return $view_factory->make($views);
            } catch (ViewNotFoundException $e) {
                //
            }
        }
        
        throw new ViewNotFoundException(
            sprintf(
                "None of the used view factories supports any of the views [%s].\nTried with:\n%s",
                implode(',', $views),
                implode(
                    "\n",
                    array_map(function (ViewFactory $v) {
                        return get_class($v);
                    }, $this->view_factories)
                )
            )
        );
    }
    
}

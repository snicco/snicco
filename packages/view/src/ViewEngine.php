<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\View\Contracts\ViewFactory;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewNotFoundException;

/**
 * @api This class is a facade to the underlying ViewFactory.
 */
final class ViewEngine
{
    
    /**
     * @var ViewFactory
     */
    private $view_factory;
    
    /**
     * The root view that was rendered.
     *
     * @var ViewInterface|null
     */
    private $rendered_view;
    
    public function __construct(ViewFactory $view_factory)
    {
        $this->view_factory = $view_factory;
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
     * @throws ViewNotFoundException
     */
    public function make($views) :ViewInterface
    {
        $view = $this->view_factory->make((array) $views);
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
    
}

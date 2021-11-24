<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;
use InvalidArgumentException;
use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Contracts\ViewComposerFactory;
use Snicco\View\Implementations\NewableInstanceViewComposerFactory;

/**
 * @api
 */
class ViewComposerCollection
{
    
    /**
     * @var array
     */
    private $composers = [];
    
    /**
     * @var ViewComposerFactory
     */
    private $composer_factory;
    
    /**
     * @var GlobalViewContext
     */
    private $global_view_context;
    
    public function __construct(?ViewComposerFactory $composer_factory = null, ?GlobalViewContext $global_view_context = null)
    {
        $this->composer_factory = $composer_factory ?? new NewableInstanceViewComposerFactory();
        $this->global_view_context = $global_view_context ?? new GlobalViewContext();
    }
    
    /**
     * @param  string|string[]  $views
     * @param  string|Closure class name or closure
     */
    public function addComposer($views, $composer) :void
    {
        $views = is_array($views) ? $views : [$views];
        
        if ($composer instanceof Closure) {
            $this->composers[] = [
                'views' => $views,
                'handler' => $composer,
            ];
            return;
        }
        
        if ( ! is_string($composer)) {
            throw new InvalidArgumentException(
                "A view composer has to be a closure or a class name"
            );
        }
        
        if ( ! class_exists($composer)) {
            throw new InvalidArgumentException(
                "[$composer] is not a valid class."
            );
        }
        
        if ( ! in_array(ViewComposer::class, class_implements($composer), true)) {
            throw new InvalidArgumentException(
                sprintf("Class [%s] does not implement [%s]", $composer, ViewComposer::class)
            );
        }
        
        $this->composers[] = [
            'views' => $views,
            'handler' => $composer,
        ];
    }
    
    /**
     * Composes a view instance with contexts in the following order: Global, Composers, Local.
     */
    public function compose(ViewInterface $view) :void
    {
        $local_context = $view->context();
        
        foreach ($this->global_view_context->get() as $name => $context) {
            $view->with($name, $context);
        }
        
        $composers = $this->matchingComposers($view);
        
        array_walk($composers, function ($composer) use ($view) {
            $c = $this->composer_factory->create($composer);
            $c->compose($view);
        });
        
        $view->with($local_context);
    }
    
    private function matchingComposers(ViewInterface $view) :array
    {
        $matching = [];
        
        foreach ($this->composers as $composer) {
            if (in_array($view->name(), $composer['views'], true)) {
                $matching[] = $composer['handler'];
            }
        }
        return $matching;
    }
    
}
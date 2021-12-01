<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Closure;
use RuntimeException;
use Snicco\Shared\ContainerAdapter;
use Snicco\View\ClosureViewComposer;
use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewComposerFactory;

class DependencyInjectionViewComposerFactory implements ViewComposerFactory
{
    
    /**
     * An array of fully qualified namespaces that will be prepended to the composer class.
     *
     * @var string[]
     */
    private array            $namespaces;
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container, array $namespaces = [])
    {
        $this->namespaces = $namespaces;
        $this->container = $container;
    }
    
    /**
     * @param  string|Closure  $composer  A class name if a string is passed.
     *
     * @return ViewComposer
     */
    public function create($composer) :ViewComposer
    {
        if ($composer instanceof Closure) {
            return $this->composerFromClosure($composer);
        }
        
        if (class_exists($composer)) {
            return $this->composerClass($composer);
        }
        
        foreach ($this->namespaces as $namespace) {
            $class = trim($namespace, '\\').'\\'.$composer;
            if (class_exists($class)) {
                return $this->composerClass($class);
            }
        }
        
        throw new RuntimeException("Composer [$composer] could not be created.");
    }
    
    private function composerFromClosure(Closure $composer_closure) :ViewComposer
    {
        return new ClosureViewComposer($composer_closure);
    }
    
    private function composerClass($composer) :ViewComposer
    {
        $composer = $this->container->make($composer);
        $this->container->instance(get_class($composer), $composer);
        return $composer;
    }
    
}
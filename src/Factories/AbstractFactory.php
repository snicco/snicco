<?php

declare(strict_types=1);

namespace Snicco\Factories;

use RuntimeException;
use Snicco\Support\Str;
use Snicco\Support\Reflector;
use Snicco\Shared\ContainerAdapter;
use Snicco\Traits\ReflectsCallable;

/**
 * This factory is a base class to build callables/closure from the DI-Container.
 */
abstract class AbstractFactory
{
    
    use ReflectsCallable;
    
    /**
     * Array of FQN from where we look for classes
     * being built
     */
    protected array            $namespaces;
    protected ContainerAdapter $container;
    
    public function __construct(array $namespaces, ContainerAdapter $container)
    {
        $this->namespaces = $namespaces;
        $this->container = $container;
    }
    
    protected function normalizeInput($raw_handler) :array
    {
        if (is_string($raw_handler)) {
            return Str::parseCallback($raw_handler, '__invoke');
        }
        
        return $raw_handler;
    }
    
    protected function checkIfCallable(array $handler) :?array
    {
        if (Reflector::isCallable($handler)) {
            return $handler;
        }
        
        if (count($handler) === 1 && method_exists($handler[0], '__invoke')) {
            return [$handler[0], '__invoke'];
        }
        
        [$class, $method] = $handler;
        
        foreach ($this->namespaces as $namespace) {
            if (Reflector::isCallable([$namespace.'\\'.$class, $method])) {
                return [$namespace.'\\'.$class, $method];
            }
        }
        
        return null;
    }
    
    protected function fail($class, $method)
    {
        $method = Str::replaceFirst('@', '', $method);
        
        throw new RuntimeException(
            "[".$class.", '".$method."'] is not a valid callable."
        );
    }
    
}
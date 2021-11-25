<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Closure;
use RuntimeException;
use Snicco\Support\Str;
use Snicco\Shared\ContainerAdapter;
use Illuminate\Support\Reflector;
use Snicco\Traits\ReflectsCallable;

use function collect;

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
        return collect($raw_handler)
            ->flatMap(function ($value) {
                if ($value instanceof Closure || ! Str::contains($value, '@')) {
                    return [$value];
                }
                
                return [Str::before($value, '@'), Str::after($value, '@')];
            })
            ->filter(fn($value) => ! empty($value))
            ->values()
            ->all();
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
        
        $matched = collect($this->namespaces)
            ->map(function ($namespace) use ($class, $method) {
                if (Reflector::isCallable([$namespace.'\\'.$class, $method])) {
                    return [$namespace.'\\'.$class, $method];
                }
            })
            ->filter(fn($value) => $value !== null);
        
        return $matched->isNotEmpty() ? $matched->first() : null;
    }
    
    protected function fail($class, $method)
    {
        $method = Str::replaceFirst('@', '', $method);
        
        throw new RuntimeException(
            "[".$class.", '".$method."'] is not a valid callable."
        );
    }
    
}
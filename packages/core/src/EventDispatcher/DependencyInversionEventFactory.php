<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Throwable;
use Snicco\Support\ReflectionDependencies;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\MappedEventFactory;
use Snicco\EventDispatcher\Exceptions\MappedEventCreationException;

final class DependencyInversionEventFactory implements MappedEventFactory
{
    
    private ReflectionDependencies $reflection_dependencies;
    
    public function __construct(ReflectionDependencies $reflection_dependencies)
    {
        $this->reflection_dependencies = $reflection_dependencies;
    }
    
    public function make(string $event_class, array $wordpress_hook_arguments) :Event
    {
        try {
            $deps = $this->reflection_dependencies->build($event_class, $wordpress_hook_arguments);
            
            return new $event_class(...$deps);
        } catch (Throwable $e) {
            throw MappedEventCreationException::becauseTheEventCouldNotBeConstructorWithArgs(
                $wordpress_hook_arguments,
                $event_class,
                $e
            );
        }
    }
    
}
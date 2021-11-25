<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use ReflectionObject;
use ReflectionMethod;
use ReflectionProperty;
use BadMethodCallException;
use Snicco\EventDispatcher\Contracts\Event;

/**
 * Takes an event object as a dependency and proxies all calls into the underlying event.
 * Public properties will be READ ONLY.
 *
 * @api
 */
final class ImmutableEvent implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    /**
     * @var Event
     */
    private $event;
    
    /**
     * @var array
     */
    private $properties = [];
    
    /**
     * @var array
     */
    private $methods = [];
    
    /**
     * @var string
     */
    private $event_class;
    
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->event_class = get_class($event);
        
        $reflection = new ReflectionObject($event);
        
        $public_properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC) ?? [];
        
        foreach ($public_properties as $public_property) {
            $name = $public_property->getName();
            $this->properties[$name] = $event->{$name};
        }
        
        $public_methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC) ?? [];
        
        foreach ($public_methods as $public_method) {
            if ($public_method->isConstructor() || $public_method->isDestructor()) {
                continue;
            }
            
            $name = $public_method->getName();
            
            $this->methods[$name] = function ($args) use ($name) {
                return call_user_func_array([$this->event, $name], $args);
            };
        }
    }
    
    public function __get($name)
    {
        if ( ! isset($this->properties[$name])) {
            throw new BadMethodCallException(
                "The property [$name] is private on the class [$this->event_class]."
            );
        }
        
        return $this->properties[$name];
    }
    
    public function __set($name, $value)
    {
        throw new BadMethodCallException(
            "The event [$this->event_class] is an action and cant be changed."
        );
    }
    
    public function __call($name, $arguments)
    {
        if (isset($this->methods[$name])) {
            $closure = $this->methods[$name];
            return $closure($arguments);
        }
        
        throw new BadMethodCallException(
            "The method [$name] is not callable on the action event [$this->event_class]."
        );
    }
    
}
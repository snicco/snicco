<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\functions
{
    
    use Closure;
    use ReflectionFunction;
    use ReflectionParameter;
    use ReflectionException;
    use InvalidArgumentException;
    use Snicco\EventDispatcher\Contracts\Event;
    use Snicco\EventDispatcher\InvalidListenerException;
    
    /**
     * @internal
     *
     * @param  Closure|string|array<string,string>  $listener
     *
     * @throws InvalidListenerException
     * @returns Closure|array<string,string>
     */
    function normalizeListener($listener)
    {
        if ($listener instanceof Closure) {
            return $listener;
        }
        
        if (is_string($listener)) {
            if ( ! class_exists($listener)) {
                throw new InvalidListenerException(
                    "The listener [$listener] is not a valid class."
                );
            }
            
            $invokable = method_exists($listener, '__invoke');
            
            if ( ! method_exists($listener, 'handle') && ! $invokable) {
                throw new InvalidListenerException(
                    "The listener [$listener] is does not have a handle method."
                );
            }
            
            return [$listener, $invokable ? '__invoke' : 'handle'];
        }
        
        if ( ! is_array($listener)) {
            throw new InvalidArgumentException('Listeners must be a string, array or closure.');
        }
        
        if ( ! class_exists($listener[0])) {
            throw new InvalidListenerException(
                "The listener [$listener[0]] is not a valid class."
            );
        }
        
        if ( ! method_exists($listener[0], $listener[1] ??= 'handle')) {
            throw new InvalidListenerException(
                "The listener [$listener[0]] does not have a method [$listener[1]]."
            );
        }
    }
    
    /**
     * @internal
     *
     * @param  Closure  $closure
     *
     * @return string
     * @throws ReflectionException
     */
    function getTypeHintedEventFromClosure(Closure $closure) :string
    {
        $reflection = new ReflectionFunction($closure);
        
        $parameters = (array) $reflection->getParameters();
        
        if ( ! count($parameters) || ! $parameters[0] instanceof ReflectionParameter) {
            throw new InvalidListenerException(
                "The closure listener must have a type hinted event as the first parameter."
            );
        }
        
        $param = $parameters[0];
        
        $type = $param->getType();
        
        if ( ! $type || ! in_array(Event::class, class_implements($type->getName()))) {
            throw new InvalidListenerException(
                'The closure listener must have a type hinted event as the first parameter.'
            );
        }
        
        return $type->getName();
    }
}
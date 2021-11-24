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
    use Snicco\EventDispatcher\Exceptions\InvalidListenerException;
    
    /**
     * @internal
     *
     * @param  Closure|string|array<string,string>  $listener
     *
     * @throws InvalidListenerException
     * @returns Closure|array<string,string>
     */
    function validatedListener($listener)
    {
        if ($listener instanceof Closure) {
            return $listener;
        }
        
        if (is_string($listener)) {
            if ( ! class_exists($listener)) {
                throw InvalidListenerException::becauseTheListenerIsNotAValidClass($listener);
            }
            
            $invokable = method_exists($listener, '__invoke');
            
            if ( ! method_exists($listener, 'handle') && ! $invokable) {
                throw InvalidListenerException::becauseTheListenerHasNoValidMethod($listener);
            }
            
            return [$listener, $invokable ? '__invoke' : 'handle'];
        }
        
        if ( ! is_array($listener)) {
            throw new InvalidArgumentException('Listeners must be a string, array or closure.');
        }
        
        if ( ! class_exists($listener[0])) {
            throw InvalidListenerException::becauseTheListenerIsNotAValidClass($listener[0]);
        }
        
        if ( ! isset($listener[1])) {
            $listener[1] = 'handle';
        }
        
        if ( ! method_exists($listener[0], $listener[1])) {
            throw InvalidListenerException::becauseTheListenerHasNoValidMethod($listener[0]);
        }
        return $listener;
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
            throw InvalidListenerException::becauseTheClosureDoesntHaveATypehintedEvent();
        }
        
        $param = $parameters[0];
        
        $type = $param->getType();
        
        if ( ! $type || ! in_array(Event::class, class_implements($type->getName()))) {
            throw InvalidListenerException::becauseTheClosureDoesntHaveATypehintedEvent();
        }
        
        return $type->getName();
    }
    
    /**
     * @internal
     *
     * @param  string  $event_name
     *
     * @return bool
     */
    function isWildCardEventListener(string $event_name) :bool
    {
        return strpos($event_name, '*') !== false;
    }
    
    /**
     * @internal
     *
     * @param  string  $listens_to
     * @param  string  $event_name
     *
     * @return bool
     */
    function wildcardPatternMatchesEventName(string $listens_to, string $event_name) :bool
    {
        $pattern = "/^".str_replace('\*', '.*', $listens_to)."$/";
        $matches = preg_match($pattern, $event_name);
        return $matches === 1;
    }
}
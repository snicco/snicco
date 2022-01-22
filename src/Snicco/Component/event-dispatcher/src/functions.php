<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\functions
{
    
    use Closure;
    use ReflectionFunction;
    use ReflectionParameter;
    use ReflectionException;
    use Snicco\Component\EventDispatcher\Exception\InvalidListener;
    
    use function count;
    
    /**
     * @internal
     *
     * @param  Closure  $closure
     *
     * @return string
     * @throws ReflectionException
     */
    function getTypeHintedObjectFromClosure(Closure $closure) :string
    {
        $reflection = new ReflectionFunction($closure);
        
        $parameters = $reflection->getParameters();
        
        if ( ! count($parameters) || ! $parameters[0] instanceof ReflectionParameter) {
            throw InvalidListener::becauseTheClosureDoesntHaveATypeHintedObject();
        }
        
        $param = $parameters[0];
        
        $type = $param->getType();
        
        if ( ! $type || empty($type->getName())) {
            throw InvalidListener::becauseTheClosureDoesntHaveATypeHintedObject();
        }
        
        return $type->getName();
    }
}
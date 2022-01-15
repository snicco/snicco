<?php

declare(strict_types=1);

namespace Snicco\Core\Utils
{
    
    use InvalidArgumentException;
    
    /**
     * @param  string|object  $class_or_object
     *
     * @framework-only
     */
    function isInterface($class_or_object, string $interface) :bool
    {
        $class = is_object($class_or_object)
            ? get_class($class_or_object)
            : $class_or_object;
        
        $interface_exists = interface_exists($interface);
        
        if (false === $interface_exists) {
            throw new InvalidArgumentException("Interface [$interface] does not exist.");
        }
        
        if ($interface === $class) {
            return true;
        }
        
        if ( ! class_exists($class) && ! interface_exists($class)) {
            return false;
        }
        
        $implements = (array) class_implements($class);
        
        return in_array($interface, $implements, true);
    }
}
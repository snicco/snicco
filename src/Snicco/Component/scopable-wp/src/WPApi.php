<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP;

use InvalidArgumentException;

use function ucwords;
use function sprintf;
use function strtolower;
use function ctype_lower;
use function preg_replace;
use function trigger_error;
use function function_exists;
use function call_user_func_array;

use const E_USER_NOTICE;

class WPApi
{
    
    const VERSION = '1.0.0';
    
    private static array $snake_cache = [];
    
    public function __call($name, $arguments)
    {
        $method = $this->methodNameToSnakeCase($name);
        $method = '\\'.$method;
        
        if ( ! function_exists($method)) {
            throw new InvalidArgumentException(
                sprintf("Called undefined WordPress function [%s].", $name)
            );
        }
        
        $this->triggerNotice($name, $method);
        
        return call_user_func_array($name, $arguments);
    }
    
    private function methodNameToSnakeCase(string $method_name)
    {
        $key = $method_name;
        
        if (isset(self::$snake_cache[$key])) {
            return self::$snake_cache[$key];
        }
        
        if ( ! ctype_lower($method_name)) {
            $method_name = preg_replace('/\s+/u', '', ucwords($method_name));
            
            $method_name = strtolower(
                preg_replace(
                    '/(.)(?=[A-Z])/u',
                    '$1'.'_',
                    $method_name
                )
            );
        }
        
        return static::$snake_cache[$key] = $method_name;
    }
    
    private function triggerNotice(string $called_instance_method, string $proxies_to) :void
    {
        trigger_error(
            sprintf(
                "Tried to call method [%s] on [%s] but its not defined.There might be an autoload conflict.\nUsed version [%s].\nProxying to global WordPress function [%s].",
                $called_instance_method,
                self::class,
                self::VERSION,
                $proxies_to
            ),
            E_USER_NOTICE
        );
    }
    
}
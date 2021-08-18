<?php

declare(strict_types=1);

namespace Snicco\Application;

use BadMethodCallException;
use Contracts\ContainerAdapter;
use SniccoAdapter\BaseContainerAdapter;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

/**
 * @mixin ApplicationMixin
 */
trait ApplicationTrait
{
    
    public static ?Application $instance = null;
    
    public static function make(string $base_path, ContainerAdapter $container = null) :Application
    {
        
        static::setApplication(
            Application::create($base_path, $container ?? new BaseContainerAdapter())
        );
        
        $app = static::getApplication();
        $app->container()->instance(ApplicationTrait::class, static::class);
        
        return $app;
        
    }
    
    public static function setApplication(?Application $application)
    {
        static::$instance = $application;
    }
    
    public static function getApplication() :?Application
    {
        return static::$instance;
    }
    
    /**
     * Invoke any matching instance method for the static method being called.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     * @throws ConfigurationException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        
        $application = static::getApplication();
        $callable = [$application, $method];
        
        if ( ! $application) {
            throw new ConfigurationException(
                'Application instance not created in '.static::class.'. '.
                'Did you miss to call '.static::class.'::make()?'
            );
        }
        
        if ( ! is_callable($callable)) {
            throw new BadMethodCallException(
                'Method '.get_class($application).'::'.$method.'() does not exist.'
            );
        }
        
        return call_user_func_array($callable, $parameters);
    }
    
}

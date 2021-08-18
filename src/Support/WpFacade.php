<?php

declare(strict_types=1);

namespace Snicco\Support;

use Mockery;
use RuntimeException;
use Mockery\Expectation;
use Mockery\MockInterface;
use Contracts\ContainerAdapter;

abstract class WpFacade
{
    
    private static ?ContainerAdapter $container;
    
    /**
     * The resolved object instances.
     */
    private static array $resolvedInstance;
    
    /**
     * Clear a resolved facade instance.
     *
     * @param  string  $name
     *
     * @return void
     */
    public static function clearResolvedInstance(string $name)
    {
        
        unset(static::$resolvedInstance[$name]);
    }
    
    public static function getFacadeContainer() :ContainerAdapter
    {
        
        return static::$container;
        
    }
    
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     *
     * @return mixed
     * @throws RuntimeException
     */
    public static function __callStatic(string $method, array $args)
    {
        
        $instance = static::getFacadeRoot();
        
        if ( ! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }
        
        return $instance->$method(...$args);
        
    }
    
    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        
        return static::resolveFacadeInstance(static::getFacadeAccessor());
        
    }
    
    /**
     * Resolve the facade root instance from the container.
     *
     * @param  object|string  $name
     *
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        
        if (is_object($name)) {
            return $name;
        }
        
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        
        if (static::$container) {
            
            return static::$resolvedInstance[$name] = static::$container->make($name);
            
        }
        
    }
    
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }
    
    /**
     * Convert the facade into a Mockery spy.
     *
     * @return MockInterface
     */
    public static function spy()
    {
        
        if ( ! static::isMock()) {
            
            $class = static::getMockableClass();
            
            $spy = $class ? Mockery::spy($class) : Mockery::spy();
            static::swap($spy);
            
            return $spy;
            
        }
    }
    
    /**
     * Determines whether a mock is set as the instance of the facade.
     *
     * @return bool
     */
    protected static function isMock()
    {
        
        $name = static::getFacadeAccessor();
        
        return isset(static::$resolvedInstance[$name])
               && static::$resolvedInstance[$name] instanceof MockInterface;
    }
    
    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        
        if ($root = static::getFacadeAccessor()) {
            return $root;
        }
    }
    
    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     *
     * @return void
     */
    public static function swap($instance)
    {
        
        static::$resolvedInstance[static::getFacadeAccessor()] = $instance;
        
        if (isset(static::$container)) {
            static::$container->swapInstance(static::getFacadeAccessor(), $instance);
        }
        
    }
    
    /**
     * Initiate a partial mock on the facade.
     *
     * @return MockInterface
     */
    public static function partialMock()
    {
        
        $name = static::getFacadeAccessor();
        
        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();
        
        return $mock->makePartial();
    }
    
    /**
     * Create a fresh mock instance for the given class.
     *
     * @return MockInterface
     */
    public static function createFreshMockInstance()
    {
        
        $mock = static::createMock();
        static::swap($mock);
        $mock->shouldAllowMockingProtectedMethods();
        
        return $mock;
        
    }
    
    /**
     * Create a fresh mock instance for the given class.
     *
     * @return MockInterface
     */
    protected static function createMock()
    {
        
        $class = static::getMockableClass();
        
        return $class ? Mockery::mock($class) : Mockery::mock();
    }
    
    /**
     * Initiate a mock expectation on the facade.
     *
     * @return Expectation
     */
    public static function shouldReceive()
    {
        
        $name = static::getFacadeAccessor();
        
        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();
        
        return $mock->shouldReceive(...func_get_args());
    }
    
    public static function reset()
    {
        
        self::clearResolvedInstances();
        self::setFacadeContainer(null);
        
    }
    
    /**
     * Clear all the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        
        static::$resolvedInstance = [];
        
    }
    
    public static function setFacadeContainer(?ContainerAdapter $container)
    {
        
        static::$container = $container;
        
    }
    
}
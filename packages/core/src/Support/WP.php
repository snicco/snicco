<?php

declare(strict_types=1);

namespace Snicco\Support;

use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;

/**
 * @mixin WordpressApiMixin
 */
class WP
{
    
    private static $instance;
    
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return static::getInstance()->$method(...$args);
    }
    
    /**
     * Initiate a mock expectation on the WordPressApi.
     *
     * @return Expectation
     */
    public static function shouldReceive()
    {
        if ( ! static::isMock(static::getInstance())) {
            static::$instance = static::createFreshMock();
        }
        
        return static::$instance->shouldReceive(...func_get_args());
    }
    
    /**
     * Initiate a partial mock on the WordPressApi.
     *
     * @return MockInterface
     */
    public static function partialMock()
    {
        static::$instance = static::createFreshMock();
        return static::$instance->makePartial();
    }
    
    /**
     * Turn the WordPress API into a spy.
     *
     * @return MockInterface
     */
    public static function spy()
    {
        static::$instance = Mockery::spy(WordpressApi::class);
        return static::$instance;
    }
    
    public static function reset()
    {
        static::$instance = new WordpressApi();
    }
    
    private static function createFreshMock()
    {
        return Mockery::mock(WordpressApi::class);
    }
    
    private static function getInstance()
    {
        if ( ! static::$instance) {
            static::$instance = new WordpressApi();
        }
        
        return static::$instance;
    }
    
    private static function isMock($instance) :bool
    {
        return $instance instanceof MockInterface;
    }
    
}
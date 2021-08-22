<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Whoops\Run;
use ReflectionClass;
use Tests\stubs\TestApp;
use Whoops\RunInterface;
use Tests\FrameworkTestCase;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PrettyPageHandler;
use Snicco\Contracts\ExceptionHandler;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class ExceptionServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_exception_handler_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            ProductionExceptionHandler::class,
            TestApp::resolve(ExceptionHandler::class)
        );
    }
    
    /** @test */
    public function the_null_error_handler_can_be_used()
    {
        $this->withAddedConfig('app.exception_handling', false)->bootApp();
        
        $this->assertInstanceOf(
            NullExceptionHandler::class,
            TestApp::resolve(ExceptionHandler::class)
        );
    }
    
    /** @test */
    public function the_error_views_are_registered()
    {
        $this->bootApp();
        $views = TestApp::config('view.paths');
        $expected = ROOT_DIR.DS.'src'.DS.'ExceptionHandling'.DS.'views';
        
        $this->assertContains($expected, $views);
    }
    
    /** @test */
    public function whoops_is_not_bound_in_non_debug_mode()
    {
        
        $this->bootApp();
        $this->withAddedConfig('app.debug', false);
        
        /** @var ProductionExceptionHandler $exception_handler */
        $exception_handler = $this->app->resolve(ExceptionHandler::class);
        $class = new ReflectionClass($exception_handler);
        $prop = $class->getProperty('whoops');
        $prop->setAccessible(true);
        $whoops = $prop->getValue($exception_handler);
        
        $this->assertNull($whoops);
        
    }
    
    /** @test */
    public function whoops_is_bound_in_debug_mode()
    {
        
        $this->withAddedConfig('app.debug', true);
        $this->bootApp();
        
        $this->assertInstanceOf(Run::class, $this->app->resolve(RunInterface::class));
        $this->assertInstanceOf(
            PrettyPageHandler::class,
            $this->app->resolve(HandlerInterface::class)
        );
        
        /** @var ProductionExceptionHandler $exception_handler */
        $exception_handler = $this->app->resolve(ExceptionHandler::class);
        $class = new ReflectionClass($exception_handler);
        $prop = $class->getProperty('whoops');
        $prop->setAccessible(true);
        $whoops = $prop->getValue($exception_handler);
        
        $this->assertInstanceOf(Run::class, $whoops);
        
    }
    
}


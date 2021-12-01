<?php

declare(strict_types=1);

namespace Tests\Core\integration\EventDispatcher;

use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\EventDispatcher\Contracts\MappedEventFactory;
use Snicco\EventDispatcher\DependencyInversionEventFactory;
use Snicco\EventDispatcher\DependencyInversionListenerFactory;

final class EventServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_event_dispatcher_can_be_resolved()
    {
        $this->withAddedConfig('app.env', 'production');
        $this->bootApp();
        $dispatcher = $this->app->resolve(Dispatcher::class);
        
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        
        $this->assertInstanceOf(
            DependencyInversionEventFactory::class,
            $this->app->resolve(MappedEventFactory::class)
        );
        $this->assertInstanceOf(
            DependencyInversionListenerFactory::class,
            $this->app->resolve(ListenerFactory::class)
        );
    }
    
    /** @test */
    public function during_testing_a_fake_dispatcher_is_used()
    {
        $this->bootApp();
        $dispatcher = $this->app->resolve(Dispatcher::class);
        
        $this->assertInstanceOf(FakeDispatcher::class, $dispatcher);
        
        $this->assertInstanceOf(
            DependencyInversionEventFactory::class,
            $this->app->resolve(MappedEventFactory::class)
        );
        $this->assertInstanceOf(
            DependencyInversionListenerFactory::class,
            $this->app->resolve(ListenerFactory::class)
        );
    }
    
    /** @test */
    public function the_event_dispatcher_is_a_singleton()
    {
        $this->bootApp();
        $dispatcher = $this->app->resolve(Dispatcher::class);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($dispatcher, $this->app->resolve(Dispatcher::class));
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use stdClass;
use Pimple\Container;
use PHPUnit\Framework\TestCase;
use Pimple\Psr11\Container as PimplePsr11;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\ListenerFactory\ContainerListenerFactory;

final class EventDispatcherWithContainerTest extends TestCase
{
    
    /** @test */
    public function listeners_can_be_resolved_from_a_psr11_container()
    {
        $container = new PimplePsr11($pimple = new Container());
        
        $pimple[ListenerWithDependency::class] = function (Container $container) {
            return new ListenerWithDependency($container[Dependency::class]);
        };
        
        $pimple[Dependency::class] = new Dependency('FOOBAR');
        
        $event_dispatcher = new BaseEventDispatcher(
            new ContainerListenerFactory($container)
        );
        
        $event = new stdClass();
        $event->value = 'foo';
        
        $event_dispatcher->listen(stdClass::class, ListenerWithDependency::class);
        
        $event = $event_dispatcher->dispatch($event);
        $this->assertSame('FOOBAR', $event->value);
    }
    
}

class ListenerWithDependency
{
    
    private Dependency $dep;
    
    public function __construct(Dependency $dep)
    {
        $this->dep = $dep;
    }
    
    public function __invoke(stdClass $event) :string
    {
        return $event->value = $this->dep->value;
    }
    
}

class Dependency
{
    
    public string $value;
    
    public function __construct(string $value)
    {
        $this->value = $value;
    }
    
}
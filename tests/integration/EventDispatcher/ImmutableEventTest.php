<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher;

use Tests\UnitTest;
use BadMethodCallException;
use Snicco\EventDispatcher\ImmutableEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\IsClassNameEvent;

class ImmutableEventTest extends UnitTest
{
    
    /** @test */
    public function public_properties_can_be_read()
    {
        $event = new ProxiedEvent('foo', 'bar');
        $immutable = new ImmutableEvent($event);
        
        $this->assertSame('foo', $immutable->foo);
    }
    
    /** @test */
    public function private_properties_cant_be_read()
    {
        $event = new ProxiedEvent('foo', 'bar');
        $immutable = new ImmutableEvent($event);
        
        try {
            $immutable->bar;
            $this->fail('accesss to private property allowed');
        } catch (BadMethodCallException $e) {
            $this->assertStringStartsWith(
                "The property [bar] is private on the class [Tests\integration\EventDispatcher\ProxiedEvent].",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function public_properties_cant_be_written()
    {
        $event = new ProxiedEvent('foo', 'bar');
        $immutable = new ImmutableEvent($event);
        
        try {
            $immutable->foo = 'foobar';
            $this->fail('write access to public property allowed.');
        } catch (BadMethodCallException $e) {
            $this->assertStringStartsWith(
                'The event [Tests\integration\EventDispatcher\ProxiedEvent] is an action and cant be changed.',
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function public_methods_can_be_called()
    {
        $event = new ProxiedEvent('foo', 'bar');
        $immutable = new ImmutableEvent($event);
        
        $this->assertSame('foo', $immutable->getFoo());
        
        $this->assertSame(['foo', 'bar'], $immutable->push(['foo'], 'bar'));
    }
    
    /** @test */
    public function other_methods_can_not_be_called()
    {
        $event = new ProxiedEvent('foo', 'bar');
        $immutable = new ImmutableEvent($event);
        
        try {
            $immutable->bogus();
            $this->fail('Bad method was called on immutable event.');
        } catch (BadMethodCallException $e) {
            $this->assertStringStartsWith(
                "The method [bogus] is not callable on the action event [Tests\integration\EventDispatcher\ProxiedEvent].",
                $e->getMessage()
            );
        }
    }
    
}

class ProxiedEvent implements Event
{
    
    use IsClassNameEvent;
    
    public  $foo;
    private $bar;
    
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function getFoo()
    {
        return $this->foo;
    }
    
    public function push(array $foo, string $bar) :array
    {
        $foo[] = $bar;
        return $foo;
    }
    
}
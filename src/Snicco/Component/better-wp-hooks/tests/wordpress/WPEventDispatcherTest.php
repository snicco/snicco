<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\wordpress;

use stdClass;
use Codeception\TestCase\WPTestCase;
use Symfony\Component\EventDispatcher\Event;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\DefaultEventDispatcher;
use Snicco\Component\BetterWPHooks\Tests\fixtures\FilterEvent;
use Snicco\Component\BetterWPHooks\Tests\fixtures\StoppableEvent;
use Snicco\Component\BetterWPHooks\Tests\fixtures\CustomNameEvent;
use Snicco\Component\BetterWPHooks\Tests\fixtures\PlainObjectEvent;

use function add_filter;

final class WPEventDispatcherTest extends WPTestCase
{
    
    /** @test */
    public function if_event_objects_implement_expose_to_wp_they_are_passed_to_the_wp_hook_system_after_the_internal_dispatcher()
    {
        add_filter(
            FilterEvent::class,
            function (FilterEvent $event) {
                $event->value = 'filtered_by_wp:'.$event->value;
            }
        );
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FilterEvent::class,
            function (FilterEvent $event) {
                $event->value = $event->value.':Filter1:';
            }
        );
        $dispatcher->listen(
            FilterEvent::class,
            function (FilterEvent $event) {
                $event->value = $event->value.'Filter2';
            }
        );
        
        $result = $dispatcher->dispatch($event = new FilterEvent('FOOBAR'));
        
        $this->assertSame($event, $result);
        $this->assertSame('filtered_by_wp:FOOBAR:Filter1:Filter2', $event->value);
    }
    
    /** @test */
    public function wordpress_filters_can_be_also_be_used_if_no_listeners_are_attached()
    {
        add_filter(FilterEvent::class, function (FilterEvent $event) {
            $event->value = $event->value.':filtered';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $result = $dispatcher->dispatch($event = new FilterEvent('FOOBAR'));
        
        $this->assertSame($event, $result);
        $this->assertSame('FOOBAR:filtered', $event->value);
    }
    
    /** @test */
    public function plain_object_events_can_be_shared_with_wordpress()
    {
        add_filter(PlainObjectEvent::class, function (PlainObjectEvent $event) {
            $event->value = 'filtered_by_wp:'.$event->value;
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            PlainObjectEvent::class,
            function (PlainObjectEvent $event) {
                $event->value = $event->value.':Filter1';
            }
        );
        
        $result = $dispatcher->dispatch($event = new PlainObjectEvent('FOOBAR'));
        
        $this->assertSame($event, $result);
        $this->assertNotInstanceOf(Event::class, $result);
        $this->assertSame('filtered_by_wp:FOOBAR:Filter1', $event->value);
    }
    
    /** @test */
    public function events_that_dont_implement_expose_to_wp_are_not_shared()
    {
        add_filter(stdClass::class, function (stdClass $event) {
            $event->value = $event->value.':filtered_by_wordpress';
        }
        );
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (stdClass $event) {
            $event->value = $event->value.':filtered_by_listener';
        });
        
        $event = new stdClass();
        $event->value = 'FOOBAR';
        
        $result = $dispatcher->dispatch($event);
        
        $this->assertSame('FOOBAR:filtered_by_listener', $result->value);
        $this->assertSame($event, $result);
    }
    
    /** @test */
    public function stopped_psr_events_are_not_shared_with_wordpress()
    {
        add_filter(StoppableEvent ::class, function (StoppableEvent $event) {
            $event->value = $event->value.':filtered_by_wordpress';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (StoppableEvent $event) {
            $event->value = $event->value.':filtered_by_listener1';
        });
        $dispatcher->listen(function (StoppableEvent $event) {
            $event->value = $event->value.':filtered_by_listener2';
            $event->stopped = true;
        });
        
        $event = new StoppableEvent('FOOBAR');
        
        $result = $dispatcher->dispatch($event);
        
        $this->assertSame('FOOBAR:filtered_by_listener1:filtered_by_listener2', $result->value);
        $this->assertSame($event, $result);
    }
    
    /** @test */
    public function events_with_custom_names_can_be_shared_with_wp()
    {
        $event = new CustomNameEvent('FOOBAR', 'foo_event');
        
        add_filter('foo_event', function (CustomNameEvent $event) {
            $event->value = 'filtered_by_wp:'.$event->value;
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            'foo_event',
            function (CustomNameEvent $event) {
                $event->value = $event->value.':Filter1';
            }
        );
        
        $result = $dispatcher->dispatch($event);
        
        $this->assertSame($event, $result);
        $this->assertNotInstanceOf(Event::class, $result);
        $this->assertSame('filtered_by_wp:FOOBAR:Filter1', $event->value);
    }
    
    private function getDispatcher() :WPEventDispatcher
    {
        return new WPEventDispatcher(
            new DefaultEventDispatcher()
        );
    }
    
}
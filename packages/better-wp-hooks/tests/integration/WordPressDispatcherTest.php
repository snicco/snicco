<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\integration;

use Codeception\TestCase\WPTestCase;
use Snicco\EventDispatcher\ImmutableEvent;
use Tests\BetterWPHooks\fixtures\ActionEvent;
use Tests\BetterWPHooks\fixtures\FilterableEvent;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\EventDispatcher\Dispatcher\WordPressDispatcher;
use Tests\BetterWPHooks\fixtures\ForbiddenToWordPressEvent;

final class WordPressDispatcherTest extends WPTestCase
{
    
    /** @test */
    public function if_events_are_filterable_users_can_hook_into_them_using_add_filter()
    {
        $count = 0;
        add_filter(FilterableEvent::class, function (FilterableEvent $event) use (&$count) {
            $count++;
            $event->val = 'filtered';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.':Filter1:';
        });
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.'Filter2';
        });
        
        $result = $dispatcher->dispatch(new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('filtered', $result->val);
        
        $this->assertSame(1, $count, 'WordPress filter not called.');
    }
    
    /** @test */
    public function wordpress_filters_can_be_also_be_used_if_no_dispatcher_filters_are_registered()
    {
        $count = 0;
        add_filter(FilterableEvent::class, function (FilterableEvent $event) use (&$count) {
            $count++;
            $event->val = $event->val.':filtered';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $result = $dispatcher->dispatch(new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('FOOBAR:filtered', $result->val);
        
        $this->assertSame(1, $count, 'WordPress filter not called.');
    }
    
    /** @test */
    public function event_objects_that_are_not_mutable_but_open_to_wordpress_will_converted_into_immutable_objects()
    {
        $count = 0;
        add_action(ActionEvent::class, function ($event) use (&$count) {
            $count++;
            $this->assertInstanceOf(ImmutableEvent::class, $event);
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->dispatch(new ActionEvent('foo', 'bar', 'baz'));
        
        $this->assertSame(1, $count, 'WordPress action was not called.');
    }
    
    /** @test */
    public function events_can_be_marked_as_forbidden_to_wordpress()
    {
        $count = 0;
        add_filter(
            ForbiddenToWordPressEvent::class,
            function (ForbiddenToWordPressEvent $event) use (&$count) {
                $count++;
                $event->val = $event->val.':filtered_by_wordpress';
            }
        );
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (ForbiddenToWordPressEvent $event) {
            $event->val = $event->val.':filtered_by_listener';
        });
        
        $result = $dispatcher->dispatch(new ForbiddenToWordPressEvent('FOOBAR'));
        
        $this->assertInstanceOf(ForbiddenToWordPressEvent::class, $result);
        $this->assertSame('FOOBAR:filtered_by_listener', $result->val);
        
        $this->assertSame(0, $count, 'WordPress filter was called when it was forbidden.');
    }
    
    private function getDispatcher() :WordPressDispatcher
    {
        return new WordPressDispatcher(
            new EventDispatcher()
        );
    }
    
}
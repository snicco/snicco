<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Snicco\Support\Arr;
use Snicco\Http\HttpKernel;
use Snicco\Contracts\ServiceProvider;
use Snicco\Http\ResponsePostProcessor;
use Snicco\EventDispatcher\Events\PreWP404;
use Snicco\EventDispatcher\Events\AdminInit;
use Snicco\EventDispatcher\Events\ResponseSent;
use Snicco\EventDispatcher\Listeners\Manage404s;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Listeners\FilterWpQuery;
use Snicco\EventDispatcher\Events\WPQueryFilterable;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Events\IncomingApiRequest;
use Snicco\EventDispatcher\Events\IncomingWebRequest;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\EventDispatcher\Events\IncomingAjaxRequest;
use Snicco\EventDispatcher\Events\IncomingAdminRequest;
use Snicco\EventDispatcher\Listeners\CreateDynamicHooks;
use Snicco\EventDispatcher\Contracts\MappedEventFactory;

class EventServiceProvider extends ServiceProvider
{
    
    private array $mapped_events = [
        'admin_init' => [
            AdminInit::class,
        ],
        'pre_handle_404' => [
            [PreWP404::class, 999],
        ],
    ];
    
    private array $ensure_first = [
        'init' => IncomingApiRequest::class,
        'admin_init' => IncomingAjaxRequest::class,
        'wp' => IncomingWebRequest::class,
    ];
    
    private array $ensure_last = [
        'do_parse_request' => WPQueryFilterable::class,
    ];
    
    private array $event_listeners = [
        
        AdminInit::class => [
            CreateDynamicHooks::class,
        ],
        
        IncomingWebRequest::class => [
            [HttpKernel::class, 'run'],
            [Manage404s::class, 'check404'],
        ],
        
        IncomingAjaxRequest::class => [
            [HttpKernel::class, 'run'],
        ],
        
        IncomingAdminRequest::class => [
            [HttpKernel::class, 'run'],
        ],
        
        IncomingApiRequest::class => [
            [HttpKernel::class, 'run'],
        ],
        
        WPQueryFilterable::class => [
            FilterWpQuery::class,
        ],
        
        ResponseSent::class => [
            [ResponsePostProcessor::class, 'maybeExit'],
        ],
        
        PreWP404::class => [
            [Manage404s::class, 'shortCircuit'],
        ],
    
    ];
    
    public function register() :void
    {
        $this->bindEventDispatcher();
        $this->bindEventListenerFactory();
        $this->bindMappedEventFactory();
        $this->bindEventMapper();
        $this->bindConfig();
    }
    
    public function bootstrap() :void
    {
        /** @var EventMapper $event_mapper */
        $event_mapper = $this->container[EventMapper::class];
        
        foreach ($this->config->get('events.mapped', []) as $hook_name => $events) {
            foreach ($events as $event) {
                $event = Arr::wrap($event);
                $event_mapper->map($hook_name, $event[0], $event[1] ?? 10);
            }
        }
        unset($hook_name);
        
        foreach ($this->config->get('events.first', []) as $hook_name => $events) {
            foreach (Arr::wrap($events) as $event) {
                $event_mapper->mapFirst($hook_name, $event);
            }
        }
        
        unset($hook_name);
        
        foreach ($this->config->get('events.last', []) as $hook_name => $events) {
            foreach (Arr::wrap($events) as $event) {
                $event_mapper->mapLast($hook_name, $event);
            }
        }
        
        unset($hook_name);
        
        /** @var Dispatcher $event_dispatcher */
        $event_dispatcher = $this->container[Dispatcher::class];
        
        foreach ($this->config->get('events.listeners', []) as $listener => $events) {
            foreach ($events as $event) {
                $event_dispatcher->listen($listener, $event);
            }
        }
    }
    
    private function bindConfig()
    {
        $this->config->extend('events.mapped', $this->mapped_events);
        $this->config->extend('events.listeners', $this->event_listeners);
        $this->config->extend('events.first', $this->ensure_first);
        $this->config->extend('events.last', $this->ensure_last);
    }
    
    private function bindEventDispatcher()
    {
        $this->container->singleton(EventDispatcher::class, EventDispatcher::class);
        
        $this->container->singleton(Dispatcher::class, function () {
            return $this->app->isRunningUnitTest()
                ? new FakeDispatcher($this->container[EventDispatcher::class])
                : $this->container[EventDispatcher::class];
        });
    }
    
    private function bindEventListenerFactory()
    {
        $this->container->singleton(
            ListenerFactory::class,
            DependencyInversionListenerFactory::class
        );
    }
    
    private function bindMappedEventFactory()
    {
        $this->container->singleton(
            MappedEventFactory::class,
            DependencyInversionEventFactory::class
        );
    }
    
    private function bindEventMapper()
    {
        $this->container->instance(
            EventMapper::class,
            new EventMapper(
                $this->container[Dispatcher::class],
                $this->container[MappedEventFactory::class]
            )
        );
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Http\HttpKernel;
use Snicco\Listeners\Manage404s;
use Snicco\Listeners\ComposeView;
use Snicco\Listeners\FilterWpQuery;
use Snicco\Contracts\ServiceProvider;
use Snicco\Http\ResponsePostProcessor;
use BetterWpHooks\Contracts\Dispatcher;
use Snicco\Listeners\CreateDynamicHooks;

class EventServiceProvider extends ServiceProvider
{
    
    private array $mapped_events = [
        'admin_init' => [
            [AdminInit::class],
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
        'do_parse_request' => WpQueryFilterable::class,
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
        
        WpQueryFilterable::class => [
            [FilterWpQuery::class, 'handleEvent'],
        ],
        
        ResponseSent::class => [
            [ResponsePostProcessor::class, 'maybeExit'],
        ],
        
        PreWP404::class => [
            [Manage404s::class, 'shortCircuit'],
        ],
        
        MakingView::class => [
            ComposeView::class,
        ],
    
    ];
    
    public function register() :void
    {
        $this->bindConfig();
    }
    
    public function bootstrap() :void
    {
        Event::make($this->container)
             ->map($this->config->get('events.mapped', []))
             ->ensureFirst($this->config->get('events.first', []))
             ->ensureLast($this->config->get('events.last', []))
             ->listeners($this->config->get('events.listeners', []))
             ->boot();
        
        //Event::listen(MakingView::class, function (ViewInterface $view) {
        //    $this->container[ViewFactoryInterface::class]->compose($view);
        //});
        
        $this->container->instance(Dispatcher::class, Event::dispatcher());
    }
    
    private function bindConfig()
    {
        $this->config->extend('events.mapped', $this->mapped_events);
        $this->config->extend('events.listeners', $this->event_listeners);
        $this->config->extend('events.first', $this->ensure_first);
        $this->config->extend('events.last', $this->ensure_last);
    }
    
}
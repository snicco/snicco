<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Listeners;

use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\EventDispatcher\Events\WPQueryFilterable;

class FilterWpQuery
{
    
    private RouteCollectionInterface $routes;
    
    public function __construct(RouteCollectionInterface $routes)
    {
        $this->routes = $routes;
    }
    
    public function handle(WPQueryFilterable $event)
    {
        if ( ! $event->server_request->isReadVerb()) {
            return;
        }
        
        $route = $this->routes->matchByUrlPattern($event->server_request);
        
        if ( ! $route instanceof Route) {
            return;
        }
        else {
            $this->routes->setCurrentRoute($route);
        }
        
        if ( ! $route->wantsToFilterWPQuery()) {
            return;
        }
        
        $event->wp->query_vars = $route->filterWpQuery();
        
        WP::removeFilter('template_redirect', 'redirect_canonical');
        WP::removeFilter('template_redirect', 'remove_old_slug');
        
        $event->do_request = false;
    }
    
    public function shouldHandle(WPQueryFilterable $event) :bool
    {
        return $event->server_request->isReadVerb();
    }
    
}
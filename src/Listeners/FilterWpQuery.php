<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Events\WpQueryFilterable;
use BetterWpHooks\Traits\ListensConditionally;
use Snicco\Contracts\RouteCollectionInterface;

class FilterWpQuery
{
    
    use ListensConditionally;
    
    private RouteCollectionInterface $routes;
    
    public function __construct(RouteCollectionInterface $routes)
    {
        $this->routes = $routes;
    }
    
    public function handleEvent(WpQueryFilterable $event) :bool
    {
        $route = $this->routes->matchByUrlPattern($event->server_request);
        
        if ( ! $route instanceof Route) {
            return $event->do_request;
        }
        else {
            $this->routes->setCurrentRoute($route);
        }
        
        if ( ! $route->wantsToFilterWPQuery()) {
            return $event->do_request;
        }
        
        $event->wp->query_vars = $route->filterWpQuery();
        
        WP::removeFilter('template_redirect', 'redirect_canonical');
        WP::removeFilter('template_redirect', 'remove_old_slug');
        
        return false;
    }
    
    public function shouldHandle(WpQueryFilterable $event) :bool
    {
        return $event->server_request->isReadVerb();
    }
    
}
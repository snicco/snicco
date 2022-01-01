<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Listeners;

use Snicco\Core\Support\WP;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\Routes;
use Snicco\Core\EventDispatcher\Events\WPQueryFilterable;

class FilterWpQuery
{
    
    /**
     * @var Routes
     */
    private $routes;
    
    public function __construct(Routes $routes)
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
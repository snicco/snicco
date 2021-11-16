<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Routing\Route;
use Snicco\Http\Psr7\Request;
use Snicco\Factories\RouteConditionFactory;

interface RouteCollectionInterface
{
    
    /**
     * Add a route to the collection
     *
     * @param  Route  $route
     *
     * @return Route
     */
    public function add(Route $route) :Route;
    
    /**
     * Find a route by its name
     *
     * @param  string  $name
     *
     * @return Route|null
     */
    public function findByName(string $name) :?Route;
    
    /**
     * Match the current request against the registered url_routes.
     *
     * @param  Request  $request
     *
     * @return Route|null
     */
    public function matchByUrlPattern(Request $request) :?Route;
    
    /**
     * Match the current request against the condition routes
     *
     * @param  Request  $request
     * @param  RouteConditionFactory  $factory
     *
     * @return Route|null
     */
    public function matchByConditions(Request $request, RouteConditionFactory $factory) :?Route;
    
    /**
     * Process the added routes after all routes have been added.
     */
    public function addToUrlMatcher() :void;
    
    /**
     * Set the current route
     *
     * @param  Route  $route
     *
     * @return void
     */
    public function setCurrentRoute(Route $route) :void;
    
}
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteMatch;

    abstract class AbstractRouteCollection
    {

        abstract public function add(Route $route) : Route;

        abstract public function match(Request $request) : RouteMatch;

        abstract public function findByName(string $name) : ?Route;

        abstract public function withWildCardUrl(string $method) : array;

        abstract public function loadIntoDispatcher(string $method = null);
    }
<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Countable;
use IteratorAggregate;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

/**
 * @api
 */
interface Routes extends Countable, IteratorAggregate
{
    
    public function add(Route $route) :void;
    
    /**
     * @throws RouteNotFound
     */
    public function getByName(string $name) :Route;
    
}
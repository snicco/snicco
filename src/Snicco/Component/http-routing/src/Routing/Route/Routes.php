<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use Countable;
use IteratorAggregate;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;

/**
 * @api
 */
interface Routes extends Countable, IteratorAggregate
{
    
    /**
     * @throws RouteNotFound
     */
    public function getByName(string $name) :Route;
    
    /**
     * @return array<string,Route> This MUST ALWAYS be an array where the key is the route name and
     *     the value an instance of {@see Route}
     */
    public function toArray() :array;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface RouteAction
{
    
    /**
     * Run the RouteAction
     * For valid return types see
     *
     * @return mixed
     * @see ResponseFactory::toResponse()
     */
    public function execute(array $args);
    
    public function getMiddleware() :array;
    
}
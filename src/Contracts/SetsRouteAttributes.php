<?php

declare(strict_types=1);

namespace Snicco\Contracts;

/** Interface used to set initial route attributes. Using this interface in the router ensures while
 *  dynamically decorating routes ensures that we will always call the correct methods on the route.
 */
interface SetsRouteAttributes
{
    
    public function middleware($middleware);
    
    public function name(string $name);
    
    public function namespace(string $namespace);
    
    public function methods($methods);
    
    public function where();
    
    public function defaults(array $defaults);
    
    public function noAction();
    
}
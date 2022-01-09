<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Application\Config;
use Snicco\Core\Routing\AbstractRouteCondition;

class RouteConditionWithDependency extends AbstractRouteCondition
{
    
    private bool   $make_it_pass;
    private Config $config;
    
    public function __construct(Config $config, bool $make_it_pass)
    {
        $this->config = $config;
        $this->make_it_pass = $make_it_pass;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return $this->make_it_pass === true;
    }
    
    public function getArguments(Request $request) :array
    {
        return [$this->config->get('foo')];
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures\Conditions;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Core\Configuration\WritableConfig;
use Snicco\HttpRouting\Routing\Condition\AbstractRouteCondition;

class RouteConditionWithDependency extends AbstractRouteCondition
{
    
    private bool           $make_it_pass;
    private WritableConfig $config;
    
    public function __construct(WritableConfig $config, bool $make_it_pass)
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
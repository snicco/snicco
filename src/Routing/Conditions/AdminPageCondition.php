<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\UrlableInterface;

class AdminPageCondition extends QueryStringCondition implements UrlableInterface
{
    
    public function toUrl(array $arguments = []) :string
    {
        
        $page = $this->query_string_arguments['page'];
        
        return WP::pluginPageUrl($page);
        
    }
    
    public function isSatisfied(Request $request) :bool
    {
        
        return true;
        
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}
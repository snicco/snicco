<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Support\Arr;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class QueryStringCondition implements Condition
{
    
    protected array $query_string_arguments;
    
    public function __construct($query_string_arguments)
    {
        $this->query_string_arguments = Arr::wrap($query_string_arguments);
    }
    
    /**
     * @return bool|void
     */
    public function isSatisfied(Request $request) :bool
    {
        $query_args = $request->getQueryParams();
        
        foreach ($this->query_string_arguments as $key => $value) {
            if ( ! in_array($key, array_keys($query_args), true)) {
                return false;
            }
        }
        
        foreach ($this->query_string_arguments as $key => $value) {
            if ($value !== $query_args[$key]) {
                return false;
            }
        }
        
        return true;
    }
    
    public function getArguments(Request $request) :array
    {
        return Arr::only($request->getQueryParams(), array_keys($this->query_string_arguments));
    }
    
}
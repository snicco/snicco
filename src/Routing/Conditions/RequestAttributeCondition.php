<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Illuminate\Support\Collection;
use Snicco\Contracts\ConditionInterface;

use function collect;

class RequestAttributeCondition implements ConditionInterface
{
    
    protected Collection $request_arguments;
    
    public function __construct(array $arguments_to_match_against)
    {
        
        $this->request_arguments = collect($arguments_to_match_against);
        
    }
    
    public function isSatisfied(Request $request) :bool
    {
        
        $request = $request->post();
        
        foreach ($this->request_arguments as $key => $value) {
            
            if ( ! in_array($key, array_keys($request), true)) {
                
                return false;
                
            }
            
        }
        
        $failed_value =
            $this->request_arguments->first(fn($value, $key) => $value !== $request[$key]);
        
        return $failed_value === null;
        
    }
    
    public function getArguments(Request $request) :array
    {
        
        return collect($request->getParsedBody())->only($this->request_arguments->keys())->all();
    }
    
}
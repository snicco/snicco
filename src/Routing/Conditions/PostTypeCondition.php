<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConditionInterface;

class PostTypeCondition implements ConditionInterface
{
    
    private string $post_type;
    
    public function __construct(string $post_type)
    {
        
        $this->post_type = $post_type;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        
        return (is_singular() && $this->post_type === get_post_type());
    }
    
    public function getArguments(Request $request) :array
    {
        
        return ['post_type' => $this->post_type];
    }
    
}

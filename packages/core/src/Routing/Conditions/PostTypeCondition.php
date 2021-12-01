<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Support\Arr;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class PostTypeCondition implements Condition
{
    
    private array $post_types;
    
    public function __construct($post_types)
    {
        $this->post_types = Arr::wrap($post_types);
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return (is_singular() && in_array(get_post_type(), $this->post_types, true));
    }
    
    public function getArguments(Request $request) :array
    {
        return ['post_type' => get_post_type()];
    }
    
}

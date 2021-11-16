<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConvertsToUrl;
use Snicco\Contracts\Condition;

class PostIdCondition implements Condition, ConvertsToUrl
{
    
    private int $post_id;
    
    public function __construct(int $post_id)
    {
        $this->post_id = $post_id;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return (is_singular() && $this->post_id === (int) get_the_ID());
    }
    
    public function getArguments(Request $request) :array
    {
        return ['post_id' => $this->post_id];
    }
    
    public function toUrl(array $arguments = []) :string
    {
        return get_permalink($this->post_id);
    }
    
}

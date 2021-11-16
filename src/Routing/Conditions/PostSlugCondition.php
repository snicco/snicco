<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class PostSlugCondition implements Condition
{
    
    private string $post_slug;
    
    public function __construct(string $post_slug)
    {
        $this->post_slug = $post_slug;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        $post = get_post();
        
        return (is_singular() && $post && $this->post_slug === $post->post_name);
    }
    
    public function getArguments(Request $request) :array
    {
        return ['post_slug' => $this->post_slug];
    }
    
}

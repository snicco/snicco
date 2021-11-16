<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class PostTemplateCondition implements Condition
{
    
    private string $post_template;
    private array  $post_types;
    
    public function __construct(string $post_template, $post_types = [])
    {
        $this->post_template = $post_template;
        $this->post_types = is_array($post_types) ? $post_types : [$post_types];
    }
    
    public function isSatisfied(Request $request) :bool
    {
        $template = get_post_meta((int) get_the_ID(), '_wp_page_template', true);
        $template = $template ? : 'default';
        
        return (is_singular($this->post_types) && $this->post_template === $template);
    }
    
    public function getArguments(Request $request) :array
    {
        return ['post_template' => $this->post_template, 'post_types' => $this->post_types];
    }
    
}

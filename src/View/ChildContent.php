<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;

class ChildContent
{
    
    private Closure $content;
    
    public function __construct(Closure $content)
    {
        $this->content = $content;
    }
    
    public function __toString()
    {
        ob_start();
        call_user_func($this->content);
        return ob_get_clean();
    }
    
}
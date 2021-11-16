<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Auth\Traits\UsesCurrentRequest;
use Snicco\Contracts\Responsable;

abstract class CreateAccountView implements Responsable
{
    
    use UsesCurrentRequest;
    
    protected string $post_to;
    
    public function postTo(string $path)
    {
        $this->post_to = $path;
        return $this;
    }
    
}
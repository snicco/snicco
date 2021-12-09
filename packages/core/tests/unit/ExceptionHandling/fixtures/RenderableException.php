<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;
use Snicco\Core\Contracts\ResponseFactory;

class RenderableException extends Exception
{
    
    public function render(ResponseFactory $factory)
    {
        return $factory->html('Foo')->withStatus(500);
    }
    
}
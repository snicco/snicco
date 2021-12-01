<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;
use Snicco\Http\ResponseFactory;

class RenderableException extends Exception
{
    
    public function render(ResponseFactory $factory)
    {
        return $factory->html('Foo')->withStatus(500);
    }
    
}
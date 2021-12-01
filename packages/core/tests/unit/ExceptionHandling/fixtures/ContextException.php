<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;

class ContextException extends Exception
{
    
    public function context() :array
    {
        return ['foo' => 'bar'];
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;

class ReturnsStringException extends Exception
{
    
    public function render()
    {
        return 'foo';
    }
    
}
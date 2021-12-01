<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;
use Tests\Codeception\shared\TestDependencies\Foo;

class LogExceptionWithFooDependency extends Exception
{
    
    public function report(Foo $foo)
    {
        $GLOBALS['test']['log'][] = $this->getMessage().':'.$foo->foo;
    }
    
}
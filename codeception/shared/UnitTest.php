<?php

declare(strict_types=1);

namespace Tests\Codeception\shared;

use Mockery;
use Codeception\PHPUnit\TestCase;

class UnitTest extends TestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $GLOBALS['test'] = [];
        Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }
    
}
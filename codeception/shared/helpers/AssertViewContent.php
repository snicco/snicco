<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use PHPUnit\Framework\Assert;
use Snicco\View\Contracts\ViewInterface;

trait AssertViewContent
{
    
    protected function assertViewContent(string $expected, $actual)
    {
        $actual = ($actual instanceof ViewInterface) ? $actual->toString() : $actual;
        
        $actual = preg_replace("/\r|\n|\t|\s{2,}/", '', $actual);
        
        Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');
    }
    
}
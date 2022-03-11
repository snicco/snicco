<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures;

use Closure;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;

trait AssertFails
{
    private function assertFailsWithMessageStarting(string $message, Closure $closure): void
    {
        try {
            $closure();
            PHPUnit::fail('The test was expected to fail a PHPUnit assertion.');
        } catch (ExpectationFailedException $e) {
            PHPUnit::assertStringStartsWith(
                $message,
                $e->getMessage(),
                'The test failed but the failure message was not as expected.'
            );
        }
    }
}

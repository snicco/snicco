<?php

declare(strict_types=1);

namespace Tests\concerns;

use Closure;
use PHPUnit\Framework\ExpectationFailedException;

trait AssertPHPUnitFailures
{
    
    private function failBecauseOfWrongAssertion($message = null)
    {
        $this->fail($message ?? 'The test subject made a wrong test assertion.');
    }
    
    private function assertFailing(Closure $closure, string $expected_failure_message)
    {
        try {
            $closure();
            $this->failBecauseOfWrongAssertion();
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith($expected_failure_message, $e->getMessage());
        }
    }
    
}
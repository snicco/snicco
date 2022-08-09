<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger\Tests\unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use Snicco\Component\MinimalLogger\ConditionalLogger;

/**
 * @internal
 */
final class ConditionalLoggerTest extends TestCase
{
    /**
     * @test
     */
    public function that_only_messages_with_the_minimum_level_are_logged(): void
    {
        $logger = new ConditionalLogger($test_logger = new TestLogger(), LogLevel::INFO);

        $logger->debug('foo');

        $this->assertCount(0, $test_logger->records);
    }

    /**
     * @test
     */
    public function that_messages_with_the_minimum_level_or_higher_are_logged(): void
    {
        $logger = new ConditionalLogger($test_logger = new TestLogger(), LogLevel::INFO);

        $logger->info('foo', [
            'baz' => 'biz',
        ]);

        $this->assertCount(1, $test_logger->records);
        $this->assertTrue($test_logger->hasRecord([
            'message' => 'foo',
            'context' => [
                'baz' => 'biz',
            ],
        ], 'info'));

        $logger->warning('bar', [
            'boo' => 'bam',
        ]);

        $this->assertCount(2, $test_logger->records);
        $this->assertTrue($test_logger->hasRecord([
            'message' => 'bar',
            'context' => [
                'boo' => 'bam',
            ],
        ], 'warning'));
    }

    /**
     * @test
     */
    public function that_exceptions_are_thrown_for_minimum_invalid_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectDeprecationMessage('bogus');
        new ConditionalLogger(new TestLogger(), 'bogus');
    }

    /**
     * @test
     */
    public function that_exceptions_are_thrown_for_invalid_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectDeprecationMessage('bogus');
        $logger = new ConditionalLogger(new TestLogger());
        $logger->log('bogus', 'foo');
    }
}

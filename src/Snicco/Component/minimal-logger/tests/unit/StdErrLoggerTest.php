<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger\Tests\unit;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Snicco\Component\MinimalLogger\StdErrLogger;
use stdClass;

use function dirname;
use function explode;
use function file_get_contents;
use function is_file;
use function touch;
use function unlink;

/**
 * @internal
 */
final class StdErrLoggerTest extends TestCase
{
    private string $log_file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log_file = dirname(__DIR__) . '/fixtures/.log/error.log';
        $this->iniSet('error_log', $this->log_file);
        if (is_file($this->log_file)) {
            unlink($this->log_file);
        }
        touch($this->log_file);
    }

    protected function tearDown(): void
    {
        if (is_file($this->log_file)) {
            unlink($this->log_file);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_errors_are_logged(): void
    {
        $this->assertSame('', file_get_contents($this->log_file));

        $logger = new StdErrLogger('snicco');

        $logger->log(LogLevel::ERROR, 'foo');

        $this->assertNotSame('', file_get_contents($this->log_file));
    }

    /**
     * @test
     */
    public function test_context_is_replaced(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(
            LogLevel::ERROR,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
            ]
        );

        $this->assertStringContainsString(
            'user calvin did something that calvin should not do.',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function test_datetime_is_replaced(): void
    {
        $logger = new StdErrLogger('snicco');

        $date = new DateTimeImmutable('12-12-2020');

        $logger->log(LogLevel::ERROR, 'calvin did something at {date}', [
            'date' => $date,
        ]);

        $this->assertStringContainsString(
            'calvin did something at 12-Dec-2020 00:00:00 UTC',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function test_object_with_to_string_method(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(
            LogLevel::ERROR,
            '{to_string}',
            [
                'to_string' => new class() {
                    public function __toString(): string
                    {
                        return 'object_to_string';
                    }
                },
            ]
        );

        $this->assertStringContainsString('object_to_string', $this->getLogContent());
    }

    /**
     * @test
     */
    public function test_object_without_to_string_method(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(LogLevel::ERROR, '{object_here}', [
            'object_here' => new stdClass(),
        ]);

        $this->assertStringContainsString('object stdClass', $this->getLogContent());
    }

    /**
     * @test
     */
    public function test_other_value_are_logged_as_their_type(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(LogLevel::ERROR, '{arr}', [
            'arr' => [],
        ]);

        $this->assertStringContainsString('array', $this->getLogContent());
    }

    /**
     * @test
     */
    public function test_log_level_and_channel_is_included(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(LogLevel::CRITICAL, 'something', []);

        $this->assertStringContainsString('snicco.CRITICAL something', $this->getLogContent());
    }

    /**
     * @test
     */
    public function additional_context_is_appended_to_the_log_entry(): void
    {
        $logger = new StdErrLogger('snicco');

        $logger->log(
            LogLevel::ERROR,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
                'foo',
                'user_id' => 1,
            ]
        );

        $log_content = $this->getLogContent();

        $this->assertStringContainsString('user calvin did something that calvin should not do.', $log_content);
        $this->assertStringContainsString("Context: ['foo', 'user_id' => 1]", $log_content);
    }

    /**
     * @test
     */
    public function test_exceptions_in_context_without_previous(): void
    {
        $logger = new StdErrLogger('my_plugin.request');
        $e = new RuntimeException('secret stuff');

        $logger->log(
            LogLevel::CRITICAL,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
                'foo',
                'user_id' => 1,
                'exception' => $e,
            ]
        );

        $content = $this->getLogContent();

        $this->assertStringContainsString('my_plugin.request.CRITICAL', $content);
        $this->assertStringContainsString('user calvin did something that calvin should not do.', $content);
        $this->assertStringContainsString("Context: ['foo', 'user_id' => 1]", $content);
        $this->assertStringContainsString('RuntimeException "secret stuff"', $content);
        $this->assertStringContainsString('in ' . __FILE__, $content);
        $this->assertStringContainsString('Stack trace:', $content);
    }

    /**
     * @test
     */
    public function test_exceptions_in_context_with_previous(): void
    {
        $logger = new StdErrLogger('my_plugin.request');

        $first_e = fn (): RuntimeException => new RuntimeException('first');

        $second_e = fn (): LogicException => new LogicException('second', 0, ($first_e)());

        $e = new RuntimeException('secret stuff', 0, ($second_e)());

        $logger->log(
            LogLevel::CRITICAL,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
                'foo',
                'user_id' => 1,
                'exception' => $e,
            ]
        );

        $content = $this->getLogContent();

        $this->assertStringContainsString('my_plugin.request.CRITICAL', $content);
        $this->assertStringContainsString('user calvin did something that calvin should not do.', $content);
        $this->assertStringContainsString("Context: ['foo', 'user_id' => 1]", $content);
        $this->assertStringContainsString('RuntimeException "secret stuff"', $content);
        $this->assertStringContainsString('in ' . __FILE__, $content);

        $this->assertStringContainsString('Caused by: LogicException "second"', $content);
        $this->assertStringContainsString('in ' . __FILE__, $content);

        $this->assertStringContainsString('Caused by: RuntimeException "first"', $content);
        $this->assertStringContainsString('in ' . __FILE__, $content);

        // only trace of previous
        $count = explode('Stack trace:', $content, -1);
        $this->assertCount(3, $count);
    }

    /**
     * @test
     */
    public function test_with_exception_as_replacement(): void
    {
        $logger = new StdErrLogger('snicco');

        $exception = new LogicException('message');

        $logger->log(LogLevel::CRITICAL, 'here {exception}', [
            'exception' => $exception,
        ]);

        $this->assertStringContainsString(
            'snicco.CRITICAL here LogicException: message',
            $this->getLogContent()
        );
    }

    private function getLogContent(): string
    {
        $content = file_get_contents($this->log_file);
        if (false === $content) {
            throw new RuntimeException('Could not get log content.');
        }

        return $content;
    }
}

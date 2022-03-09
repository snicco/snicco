<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Snicco\Bundle\HttpRouting\StdErrLogger;
use stdClass;

use function dirname;
use function explode;
use function file_get_contents;
use function ini_set;
use function is_file;
use function touch;
use function unlink;

final class StdErrLoggerTest extends TestCase
{
    private string $prev_error_log;

    private string $log_file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log_file = dirname(__DIR__) . '/fixtures/error.log';
        $prev = ini_set('error_log', $this->log_file);
        if (false === $prev) {
            throw new RuntimeException('Could not set php ini setting for error_log.');
        }
        $this->prev_error_log = $prev;
        if (is_file($this->log_file)) {
            unlink($this->log_file);
            touch($this->log_file);
        } else {
            touch($this->log_file);
        }
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->prev_error_log);
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

        $logger = new StdErrLogger();

        $logger->log(LogLevel::ERROR, 'foo');

        $this->assertNotSame('', file_get_contents($this->log_file));
    }

    /**
     * @test
     */
    public function test_context_is_replaced(): void
    {
        $logger = new StdErrLogger();

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
        $logger = new StdErrLogger();

        $date = new DateTimeImmutable('12-12-2020');

        $logger->log(
            LogLevel::ERROR,
            'calvin did something at {date}',
            [
                'date' => $date,
            ]
        );

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
        $logger = new StdErrLogger();

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

        $this->assertStringContainsString(
            'object_to_string',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function test_object_without_to_string_method(): void
    {
        $logger = new StdErrLogger();

        $logger->log(
            LogLevel::ERROR,
            '{object_here}',
            [
                'object_here' => new stdClass(),
            ]
        );

        $this->assertStringContainsString(
            'object stdClass',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function test_other_value_are_logged_as_their_type(): void
    {
        $logger = new StdErrLogger();

        $logger->log(
            LogLevel::ERROR,
            '{arr}',
            [
                'arr' => [],
            ]
        );

        $this->assertStringContainsString(
            'array',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function test_log_level_and_channel_is_included(): void
    {
        $logger = new StdErrLogger();

        $logger->log(LogLevel::CRITICAL, 'something', []);

        $this->assertStringContainsString(
            'request.CRITICAL something',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function additional_context_is_appended_to_the_log_entry(): void
    {
        $logger = new StdErrLogger();

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
        $this->assertStringContainsString(
            'user calvin did something that calvin should not do.',
            $log_content
        );
        $this->assertStringContainsString(
            "Context: ['foo', 'user_id' => 1]",
            $log_content
        );
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

        $previous = new LogicException('previous');

        $e = new RuntimeException('secret stuff', 0, $previous);

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

        $this->assertStringContainsString('Caused by: LogicException "previous"', $content);
        $this->assertStringContainsString('in ' . __FILE__, $content);

        // only trace of previous
        $count = explode('Stack trace', $content);
        $this->assertCount(2, $count);
    }

    /**
     * @test
     */
    public function test_with_exception_as_replacement(): void
    {
        $logger = new StdErrLogger();

        $exception = new LogicException('message');

        $logger->log(
            LogLevel::CRITICAL,
            'here {exception}',
            [
                'exception' => $exception,
            ]
        );

        $this->assertStringContainsString('request.CRITICAL here LogicException: message', $this->getLogContent());
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

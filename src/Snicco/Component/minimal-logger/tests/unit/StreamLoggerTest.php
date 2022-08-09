<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger\Tests\unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Snicco\Component\MinimalLogger\StreamLogger;

use function dirname;
use function file_get_contents;
use function is_file;
use function touch;
use function unlink;

/**
 * @internal
 */
final class StreamLoggerTest extends TestCase
{
    private string $log_file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log_file = dirname(__DIR__) . '/fixtures/.log/stream.log';
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
    public function that_entries_are_logged_to_a_custom_file(): void
    {
        $logger = new StreamLogger($this->log_file, 'snicco');

        $logger->log(
            LogLevel::ERROR,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
            ]
        );

        $this->assertStringContainsString(
            'snicco.ERROR user calvin did something that calvin should not do.',
            $this->getLogContent()
        );
    }

    /**
     * @test
     */
    public function that_the_log_directory_is_created_if_it_does_not_exist(): void
    {
        $this->log_file = dirname($this->log_file) . '/some-dir/stream.log';

        if (is_file($this->log_file)) {
            unlink($this->log_file);
        }

        $logger = new StreamLogger($this->log_file, 'snicco');

        $logger->log(
            LogLevel::ERROR,
            'user {user_name} did something that {user_name} should not do.',
            [
                'user_name' => 'calvin',
            ]
        );

        $this->assertStringContainsString(
            'snicco.ERROR user calvin did something that calvin should not do.',
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

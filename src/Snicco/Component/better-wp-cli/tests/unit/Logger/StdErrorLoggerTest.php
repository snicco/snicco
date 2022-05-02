<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Logger;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Input\ArrayInput;
use Snicco\Component\BetterWPCLI\Logger\StdErrLogger;
use Snicco\Component\BetterWPCLI\Tests\InMemoryStream;
use Throwable;

use function dirname;
use function file_get_contents;
use function is_file;
use function touch;
use function unlink;

/**
 * @internal
 */
final class StdErrorLoggerTest extends TestCase
{
    use InMemoryStream;

    private string $prev_error_log;

    private string $log_file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log_file = dirname(__DIR__, 2) . '/fixtures/error.log';
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
    public function that_the_exception_is_logged_correctly(): void
    {
        $logger = new StdErrLogger('snicco');
        $e = new InvalidArgumentException('foo');
        $this->setLineOnException($e, 1);

        $input = new ArrayInput($this->getInMemoryStream());

        $logger->logError($e, 'snicco foocommand', $input);

        $contents = $this->getLogContents();
        $this->assertStringStartsWith('[', $contents);
        $this->assertStringContainsString(
            'snicco/better-wp-cli.CRITICAL: Error thrown while running command [snicco foocommand].',
            $contents
        );
        $this->assertStringContainsString('Message: foo', $contents);
        $this->assertStringContainsString(
            'Exception: InvalidArgumentException at ' . $e->getFile() . ' on line ' . (string) $e->getLine(),
            $contents
        );
    }

    /**
     * @test
     */
    public function that_a_command_error_is_logged_correctly(): void
    {
        $logger = new StdErrLogger('snicco');

        $input = new ArrayInput($this->getInMemoryStream());

        $logger->logCommandFailure(10, 'snicco foocommand', $input);

        $contents = $this->getLogContents();
        $this->assertStringStartsWith('[', $contents);
        $this->assertStringContainsString(
            'snicco/better-wp-cli.DEBUG: Command [snicco foocommand] exited with code [10].',
            $contents
        );
    }

    private function getLogContents(): string
    {
        $contents = file_get_contents($this->log_file);
        if (false === $contents) {
            throw new RuntimeException('Could not get log contents');
        }

        return $contents;
    }

    private function setLineOnException(Throwable $e, int $line): void
    {
        $prop = new ReflectionProperty($e, 'line');
        $prop->setAccessible(true);
        $prop->setValue($e, $line);
        $prop->setAccessible(false);
    }
}

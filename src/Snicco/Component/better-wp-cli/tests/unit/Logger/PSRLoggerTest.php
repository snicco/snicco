<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Input\ArrayInput;
use Snicco\Component\BetterWPCLI\Logger\PSRLogger;
use Snicco\Component\BetterWPCLI\Tests\InMemoryStream;

/**
 * @internal
 */
final class PSRLoggerTest extends TestCase
{
    use InMemoryStream;

    /**
     * @test
     */
    public function that_the_exception_is_logged_correctly(): void
    {
        $logger = new PSRLogger($psr_logger = new TestLogger());

        $e = new RuntimeException('foo');

        $input = new ArrayInput($this->getInMemoryStream());

        $logger->logError($e, 'snicco foocommand', $input);

        $this->assertTrue(
            $psr_logger->hasCritical(
                [
                    'message' => 'Uncaught exception while running command [snicco foocommand]',
                    'context' => [
                        'exception' => $e,
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function that_the_command_failure_is_logged_correctly(): void
    {
        $logger = new PSRLogger($psr_logger = new TestLogger());

        $input = new ArrayInput($this->getInMemoryStream());

        $logger->logCommandFailure(2, 'snicco foocommand', $input);

        $this->assertTrue(
            $psr_logger->hasWarning(
                [
                    'message' => 'Command [snicco foocommand] exited with status code [2]',
                ]
            )
        );
    }
}

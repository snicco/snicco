<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use PHPUnit\Framework\TestCase;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Event\TerminatedResponse;
use Snicco\Bundle\HttpRouting\ResponsePostProcessor;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class ResponsePostProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function test_response_post_processor_listens_to_response_sent_event(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::testing(),
            Directories::fromDefaults(dirname(__DIR__) . '/fixtures')
        );

        $kernel->boot();

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $kernel->container()->make(EventDispatcher::class);

        /** @var ResponsePostProcessor $response_post_processor */
        $response_post_processor = $kernel->container()->make(ResponsePostProcessor::class);

        $this->assertFalse($response_post_processor->did_shutdown);

        $dispatcher->dispatch(new TerminatedResponse());

        /**
         * @psalm-suppress DocblockTypeContradiction
         */
        $this->assertTrue($response_post_processor->did_shutdown);
    }
}

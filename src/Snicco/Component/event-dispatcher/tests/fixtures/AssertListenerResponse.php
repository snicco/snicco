<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

use PHPUnit\Framework\Assert;

use function get_class;
use function is_object;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\EventDispatcher
 */
trait AssertListenerResponse
{
    private function respondedToEvent($event, string $key, $response): void
    {
        if (is_object($event)) {
            $event = get_class($event);
        }

        $GLOBALS['test']['sniccowp_listeners'][$event][$key] = $response;
    }

    private function assertListenerRun(string $event, string $key, string $expected): void
    {
        Assert::assertArrayHasKey(
            $event,
            $GLOBALS['test']['sniccowp_listeners'],
            sprintf('No listeners were called for the event [%s].', $event)
        );

        Assert::assertArrayHasKey(
            $key,
            $GLOBALS['test']['sniccowp_listeners'][$event],
            sprintf('No listeners with key [%s] were called for the event [%s].', $key, $event)
        );

        Assert::assertSame(
            $expected,
            $actual = $GLOBALS['test']['sniccowp_listeners'][$event][$key],
            sprintf(
                'The response from the listener with key [%s] was [%s]. Expected: [%s].',
                $key,
                (string) $actual,
                $expected
            )
        );
    }

    private function assertListenerNotRun($event, string $key): void
    {
        if (isset($GLOBALS['test']['sniccowp_listeners'][$event])) {
            Assert::assertArrayNotHasKey(
                $key,
                $GLOBALS['test']['sniccowp_listeners'][$event],
                sprintf('The listener with key [%s] was run.', $key)
            );
        } else {
            $this->assertTrue(true);
        }
    }

    private function resetListenersResponses(): void
    {
        $GLOBALS['test']['sniccowp_listeners'] = [];
    }
}

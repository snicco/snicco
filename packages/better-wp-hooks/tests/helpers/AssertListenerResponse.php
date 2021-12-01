<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\helpers;

use PHPUnit\Framework\Assert;

trait AssertListenerResponse
{
    
    private function respondedToEvent($event, string $key, $response)
    {
        if (is_object($event)) {
            $event = get_class($event);
        }
        $GLOBALS['test']['sniccowp_listeners'][$event][$key] = $response;
    }
    
    private function assertListenerRun($event, string $key, $expected)
    {
        Assert::assertArrayHasKey(
            $event,
            $GLOBALS['test']['sniccowp_listeners'],
            "No listeners were called for the event [$event]."
        );
        
        Assert::assertArrayHasKey(
            $key,
            $GLOBALS['test']['sniccowp_listeners'][$event],
            "No listeners with key [$key] were called for the event [$event]."
        );
        
        Assert::assertSame(
            $expected,
            $actual = $GLOBALS['test']['sniccowp_listeners'][$event][$key],
            "The response from the listener with key [$key] was [$actual]. Expected: [$expected]."
        );
    }
    
    private function assertListenerNotRun($event, string $key)
    {
        if (isset($GLOBALS['test']['sniccowp_listeners'][$event])) {
            Assert::assertArrayNotHasKey(
                $key,
                $GLOBALS['test']['sniccowp_listeners'][$event],
                "The listener with key [$key] was run."
            );
        }
        else {
            $this->assertTrue(true);
        }
    }
    
    private function resetListenersResponses()
    {
        $GLOBALS['test']['sniccowp_listeners'] = [];
    }
    
}
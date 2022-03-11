<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Component\BetterWPHooks\WPHookAPI;
use stdClass;

/**
 * @internal
 */
final class ScopableWPTest extends WPTestCase
{
    /**
     * @test
     */
    public function test_exception_if_the_global_variable_for_a_hook_is_not_an_instance_of__w_p__hook(): void
    {
        $GLOBALS['wp_filter']['foo_filter'] = new stdClass();

        $wp = new WPHookAPI();
        $this->assertNull($wp->getHook('bar_filter'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "The registered hook [foo_filter] has to be an instance of WP_Hook.\nGot: [object]."
        );

        $wp->getHook('foo_filter');
    }
}

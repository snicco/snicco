<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP\Tests\wordpress;

use BadMethodCallException;
use Codeception\TestCase\WPTestCase;
use Exception;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\ScopableWP\Tests\fixtures\TestWPApi;
use WP_Post;
use WP_User;

use function add_action;
use function add_filter;
use function apply_filters;
use function current_user_can;
use function do_action;
use function error_reporting;
use function get_current_user_id;
use function wp_cache_get;
use function wp_cache_set;
use function wp_set_current_user;

use const E_ALL;
use const E_USER_NOTICE;

/**
 * @api integration tests that load WordPress code.
 */
final class ScopableWPTest extends WPTestCase
{

    /**
     * @test
     */
    public function methods_that_exists_will_be_called_on_the_subject(): void
    {
        $wp_api = new TestWPApi();
        $this->assertSame('method1', $wp_api->method1());
    }

    /**
     * @test
     */
    public function methods_that_dont_exists_in_the_global_namespace_will_throw_an_exception(): void
    {
        try {
            $wp_api = new TestWPApi();
            $wp_api->bogus();
            $this->fail('An exception should have been thrown');
        } catch (BadMethodCallException $e) {
            $this->assertSame(
                "Method [bogus] is not defined on class [Snicco\Component\ScopableWP\Tests\\fixtures\TestWPApi] and neither [\wp_bogus] nor [\bogus] are defined in the global namespace.",
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function calling_a_method_via_the_call_magic_method_will_trigger_a_notice(): void
    {
        $wp_api = new TestWPApi();

        $old = error_reporting();
        error_reporting(E_ALL);

        try {
            $wp_api->cacheGetMultiple(['foo', 'bar']);
            $this->fail('No warning triggered.');
        } catch (Exception $e) {
            $this->assertSame(E_USER_NOTICE, $e->getCode());
            $this->assertStringContainsString(
                'There might be an autoload conflict',
                $e->getMessage()
            );
        } finally {
            error_reporting($old);
        }
    }

    /**
     * @test
     */
    public function methods_dont_exist_due_to_autoloading_conflicts_will_be_proxied_to_the_underlying_wordpress_function(
    ): void
    {
        $user = $this->factory()->user->create_and_get();
        wp_set_current_user($user->ID);

        $wp_api = new TestWPApi();

        $this->assertSame($user->ID, get_current_user_id());
        $this->assertSame($user->ID, $wp_api->getCurrentUserId());
    }

    /**
     * @test
     */
    public function the_wp_prefix_will_be_prepended_to_non_existing_proxied_functions(): void
    {
        wp_cache_set('foo', 'bar');
        wp_cache_set('baz', 'biz');

        $this->assertSame('bar', wp_cache_get('foo'));
        $this->assertSame('biz', wp_cache_get('baz'));

        $wp_api = new TestWPApi();
        $wp_api->cacheFlush();

        $this->assertSame(false, wp_cache_get('foo'));
        $this->assertSame(false, wp_cache_get('baz'));
    }

    /**
     * @test
     */
    public function test_class_can_be_extended(): void
    {
        $class = new class extends ScopableWP {

            public function getCurrentUserId(): int
            {
                return 1000;
            }

        };

        $this->assertSame(1000, $class->getCurrentUserId());
    }

    /**
     * @test
     */
    public function test_doAction(): void
    {
        $count = 0;
        add_action('foobar', function (string $foo, string $bar) use (&$count) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $count++;
        }, 10, 2);

        $wp = new ScopableWP();

        $wp->doAction('foobar', 'foo', 'bar');
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function test_applyFilters(): void
    {
        add_filter('foobar', function (string $foo, string $bar) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            return 'filtered';
        }, 10, 2);

        $wp = new ScopableWP();

        $res = $wp->applyFilters('foobar', 'foo', 'bar');
        $this->assertSame('filtered', $res);
    }

    /**
     * @test
     */
    public function test_addAction(): void
    {
        $count = 0;

        $wp = new ScopableWP();

        $wp->addAction('foobar', function (string $foo, string $bar) use (&$count) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $count++;
        }, 10, 2);

        do_action('foobar', 'foo', 'bar');
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function test_addFilter(): void
    {
        $wp = new ScopableWP();
        $wp->addFilter('foobar', function (string $foo, string $bar) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            return 'filtered';
        }, 10, 2);

        $res = apply_filters('foobar', 'foo', 'bar');
        $this->assertSame('filtered', $res);
    }

    /**
     * @test
     */
    public function test_isUserLoggedIn(): void
    {
        $wp = new ScopableWP();
        $this->assertFalse($wp->isUserLoggedIn());

        wp_set_current_user(1);

        $this->assertTrue($wp->isUserLoggedIn());

        wp_set_current_user(0);

        $this->assertFalse($wp->isUserLoggedIn());
    }

    /**
     * @test
     */
    public function test_getCurrentUser_getCurrentUserId(): void
    {
        $user = $this->factory()->user->create_and_get();

        $wp = new ScopableWP();
        $this->assertNotEquals($user, $wp->getCurrentUser());

        wp_set_current_user($user->ID);

        $this->assertEquals($user, $wp->getCurrentUser());
        $this->assertEquals($user->ID, $wp->getCurrentUserId());
    }

    /**
     * @test
     */
    public function test_caching_methods(): void
    {
        $wp = new ScopableWP();
        $this->assertFalse($wp->cacheGet('foo'));

        $wp->cacheSet('foo', 'bar');
        $this->assertSame('bar', $wp->cacheGet('foo'));

        $wp->cacheDelete('foo');
        $this->assertSame(false, $wp->cacheGet('foo'));

        $this->assertFalse($wp->cacheGet('foo', 'foo_group'));

        $wp->cacheSet('foo', 'bar', 'foo_group');
        $this->assertSame('bar', $wp->cacheGet('foo', 'foo_group'));
        $this->assertSame(false, $wp->cacheGet('foo', 'bar_group'));

        $wp->cacheDelete('foo', 'bar_group');
        $this->assertSame('bar', $wp->cacheGet('foo', 'foo_group'));

        $wp->cacheDelete('foo', 'foo_group');
        $this->assertSame(false, $wp->cacheGet('foo', 'foo_group'));
    }

    /**
     * @test
     */
    public function test_currentUserCan(): void
    {
        /** @var WP_User $user1 */
        $user1 = $this->factory()->user->create_and_get();
        $user1->add_cap('foo_cap');

        /** @var WP_User $user2 */
        $user2 = $this->factory()->user->create_and_get();
        $user2->add_cap('bar_cap');

        $wp = new ScopableWP();

        wp_set_current_user($user1);
        $this->assertTrue($wp->currentUserCan('foo_cap'));
        $this->assertFalse($wp->currentUserCan('bar_cap'));

        wp_set_current_user($user2);

        $this->assertTrue($wp->currentUserCan('bar_cap'));
        $this->assertFalse($wp->currentUserCan('foo_cap'));
    }

    /**
     * @test
     */
    public function test_currentUserCanWithArgs(): void
    {
        /** @var WP_User $user1 */
        $user1 = $this->factory()->user->create_and_get(['role' => 'author']);
        /** @var WP_Post $post1 */
        $post1 = $this->factory()->post->create_and_get([
            'post_author' => $user1->ID,
        ]);

        /** @var WP_User $user2 */
        $user2 = $this->factory()->user->create_and_get(['role' => 'author']);

        /** @var WP_Post $post2 */
        $post2 = $this->factory()->post->create_and_get([
            'post_author' => $user2->ID,
        ]);

        wp_set_current_user($user1);

        $wp = new ScopableWP();

        $this->assertTrue(current_user_can('edit_post', $post1->ID));
        $this->assertTrue($wp->currentUserCan('edit_post', $post1->ID));
        $this->assertFalse(current_user_can('edit_post', $post2->ID));
        $this->assertFalse($wp->currentUserCan('edit_post', $post2->ID));

        wp_set_current_user($user2);

        $this->assertTrue(current_user_can('edit_post', $post2->ID));
        $this->assertTrue($wp->currentUserCan('edit_post', $post2->ID));

        $this->assertFalse(current_user_can('edit_post', $post1->ID));
        $this->assertFalse($wp->currentUserCan('edit_post', $post1->ID));
    }

}
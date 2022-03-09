<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPAPI\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use LogicException;
use RuntimeException;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\BetterWPAPI\Tests\fixtures\TestWPAPI;
use stdClass;
use WP_Post;
use WP_UnitTest_Factory;
use WP_User;

use function add_action;
use function add_filter;
use function apply_filters;
use function current_user_can;
use function do_action;
use function wp_set_current_user;

/**
 * @psalm-suppress UndefinedMagicMethod
 */
final class BetterWPAPITest extends WPTestCase
{
    /**
     * @test
     */
    public function the_class_can_be_extended(): void
    {
        $wp_api = new TestWPAPI();
        $this->assertSame('customMethod', $wp_api->customMethod());
    }

    /**
     * @test
     */
    public function test_doAction(): void
    {
        $called = false;
        add_action('foobar', function (string $foo, string $bar) use (&$called) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $called = true;
        }, 10, 2);

        $wp = new TestWPAPI();

        $wp->doAction('foobar', 'foo', 'bar');
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function test_applyFilters(): void
    {
        add_filter('foobar', function (string $foo, string $bar, array $baz) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $this->assertSame(['baz', 'biz'], $baz);
            return 'filtered';
        }, 10, 3);

        $wp = new TestWPAPI();

        $res = $wp->applyFilters('foobar', 'foo', 'bar', ['baz', 'biz']);
        $this->assertSame('filtered', $res);
    }

    /**
     * @test
     */
    public function test_applyFiltersStrict(): void
    {
        add_filter('filter1', function (string $foo, string $bar, array $baz) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $this->assertSame(['baz', 'biz'], $baz);
            return 'filtered';
        }, 10, 3);

        add_filter('filter2', function (string $foo, string $bar, array $baz) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $this->assertSame(['baz', 'biz'], $baz);
            return 1;
        }, 10, 3);

        $wp = new TestWPAPI();

        $this->assertSame('filtered', $wp->applyFiltersStrict('filter1', 'foo', 'bar', ['baz', 'biz']));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Initial value for filter [filter2] is string. Returned [integer].');
        $wp->applyFiltersStrict('filter2', 'foo', 'bar', ['baz', 'biz']);
    }

    /**
     * @test
     */
    public function test_applyFiltersStrict_with_object_works(): void
    {
        add_filter('filter1', function (stdClass $std) {
            $std->foo = 'foo';
            return $std;
        }, 10, 3);

        add_filter('filter1', function (stdClass $class) {
            $this->assertTrue(isset($class->foo));
            $class->bar = 'bar';
            return $class;
        }, 10, 3);

        $wp = new TestWPAPI();

        $std = new stdClass();

        $res = $wp->applyFiltersStrict('filter1', $std);

        $this->assertSame('foo', $res->foo ?? 'null');
        $this->assertSame('bar', $res->bar ?? 'null');
    }

    /**
     * @test
     */
    public function test_applyFiltersStrict_with_object_fails(): void
    {
        add_filter('filter1', function (stdClass $std) {
            $std->foo = 'bar';
            return $std;
        }, 10, 3);

        add_filter('filter1', function (stdClass $class) {
            $this->assertTrue(isset($class->foo));
            return new TestWPAPI();
        }, 10, 3);

        $wp = new TestWPAPI();

        $std = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Initial value for filter [filter1] is an instance of [stdClass]. Returned [Snicco\Component\BetterWPAPI\Tests\fixtures\TestWPAPI].'
        );

        $wp->applyFiltersStrict('filter1', $std);
    }

    /**
     * @test
     */
    public function test_addAction(): void
    {
        $called = false;

        $wp = new TestWPAPI();

        $wp->addAction('foobar', function (string $foo, string $bar) use (&$called) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            $called = true;
        }, 10, 2);

        do_action('foobar', 'foo', 'bar');
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function test_addFilter(): void
    {
        $wp = new TestWPAPI();
        $wp->addFilter('foobar', function (string $foo, string $bar) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);
            return 'filtered';
        }, 10, 2);

        /** @psalm-suppress TooManyArguments */
        $res = apply_filters('foobar', 'foo', 'bar');
        $this->assertSame('filtered', $res);
    }

    /**
     * @test
     */
    public function test_removeFilter(): void
    {
        $cb = /** @return never */
            function () {
                throw new RuntimeException('not removed');
            };

        add_action('foo', $cb);

        $wp = new TestWPAPI();
        $wp->removeFilter('foo', $cb);

        do_action('foo');

        $cb = /** @return never */
            function () {
                throw new RuntimeException('not removed');
            };

        add_action('foo', $cb);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not removed');

        do_action('foo');
    }

    /**
     * @test
     */
    public function test_isUserLoggedIn(): void
    {
        $wp = new TestWPAPI();
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
        /** @var WP_UnitTest_Factory $factory */
        $factory = $this->factory();
        /** @var WP_User $user */
        $user = $factory->user->create_and_get();

        $wp = new TestWPAPI();
        $this->assertNotEquals($user, $wp->currentUser());

        wp_set_current_user($user->ID);

        $this->assertEquals($user, $wp->currentUser());
        $this->assertEquals($user->ID, $wp->currentUserId());
    }

    /**
     * @test
     */
    public function test_caching_methods(): void
    {
        $wp = new TestWPAPI();
        $this->assertFalse($wp->cacheGet('foo'));

        $wp->cacheSet('foo', 'bar');
        $this->assertSame('bar', $wp->cacheGet('foo'));

        $wp->cacheDelete('foo');
        $this->assertFalse($wp->cacheGet('foo'));

        $this->assertFalse($wp->cacheGet('foo', 'foo_group'));

        $wp->cacheSet('foo', 'bar', 'foo_group');
        $this->assertSame('bar', $wp->cacheGet('foo', 'foo_group'));
        $this->assertFalse($wp->cacheGet('foo', 'bar_group'));

        $wp->cacheDelete('foo', 'bar_group');
        $this->assertSame('bar', $wp->cacheGet('foo', 'foo_group'));

        $wp->cacheDelete('foo', 'foo_group');
        $this->assertFalse($wp->cacheGet('foo', 'foo_group'));
    }

    /**
     * @test
     */
    public function test_currentUserCan(): void
    {
        /** @var WP_UnitTest_Factory $factory */
        $factory = $this->factory();

        /** @var WP_User $user1 */
        $user1 = $factory->user->create_and_get();
        $user1->add_cap('foo_cap');

        /** @var WP_User $user2 */
        $user2 = $factory->user->create_and_get();
        $user2->add_cap('bar_cap');

        $wp = new TestWPAPI();

        wp_set_current_user($user1->ID);
        $this->assertTrue($wp->currentUserCan('foo_cap'));
        $this->assertFalse($wp->currentUserCan('bar_cap'));

        wp_set_current_user($user2->ID);

        $this->assertTrue($wp->currentUserCan('bar_cap'));
        $this->assertFalse($wp->currentUserCan('foo_cap'));
    }

    /**
     * @test
     */
    public function test_currentUserCanWithArgs(): void
    {
        /** @var WP_UnitTest_Factory $factory */
        $factory = $this->factory();

        /** @var WP_User $user1 */
        $user1 = $factory->user->create_and_get([
            'role' => 'author',
        ]);
        /** @var WP_Post $post1 */
        $post1 = $factory->post->create_and_get([
            'post_author' => $user1->ID,
        ]);

        /** @var WP_User $user2 */
        $user2 = $factory->user->create_and_get([
            'role' => 'author',
        ]);

        /** @var WP_Post $post2 */
        $post2 = $factory->post->create_and_get([
            'post_author' => $user2->ID,
        ]);

        wp_set_current_user($user1->ID);

        $wp = new TestWPAPI();

        $this->assertTrue(current_user_can('edit_post', $post1->ID));
        $this->assertTrue($wp->currentUserCan('edit_post', $post1->ID));
        $this->assertFalse(current_user_can('edit_post', $post2->ID));
        $this->assertFalse($wp->currentUserCan('edit_post', $post2->ID));

        wp_set_current_user($user2->ID);

        $this->assertTrue(current_user_can('edit_post', $post2->ID));
        $this->assertTrue($wp->currentUserCan('edit_post', $post2->ID));

        $this->assertFalse(current_user_can('edit_post', $post1->ID));
        $this->assertFalse($wp->currentUserCan('edit_post', $post1->ID));
    }

    /**
     * @test
     */
    public function test_wp_nonce(): void
    {
        $wp = new BetterWPAPI();

        $this->assertFalse($wp->verifyNonce('foo_nonce', 'foo_action'));

        $valid_nonce = $wp->createNonce('foo_action');

        $this->assertTrue($wp->verifyNonce($valid_nonce, 'foo_action'));

        $this->assertFalse($wp->verifyNonce($valid_nonce, 'bar_action'));
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP\Tests\wordpress;

use Mockery;
use Exception;
use BadMethodCallException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\ScopableWP\Tests\fixtures\TestWPApi;
use Snicco\Component\ScopableWP\Tests\fixtures\ClientClass;

use function do_action;
use function add_action;
use function add_filter;
use function wp_cache_set;
use function wp_cache_get;
use function apply_filters;
use function error_reporting;
use function wp_set_current_user;
use function get_current_user_id;

use const E_ALL;
use const E_USER_NOTICE;

/**
 * @api integration tests that load WordPress code.
 */
final class ScopableWPTest extends WPTestCase
{
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function methods_that_exists_will_be_called_on_the_subject()
    {
        $wp_api = new TestWPApi();
        $this->assertSame('method1', $wp_api->method1());
    }
    
    /** @test */
    public function methods_that_dont_exists_in_the_global_namespace_will_throw_an_exception()
    {
        try {
            $wp_api = new TestWPApi();
            $wp_api->bogus();
            $this->fail("An exception should have been thrown");
        } catch (BadMethodCallException $e) {
            $this->assertSame(
                "Method [bogus] is not defined on class [Snicco\Component\ScopableWP\Tests\\fixtures\TestWPApi] and neither [\wp_bogus] nor [\bogus] are defined in the global namespace.",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function calling_a_method_via_the_call_magic_method_will_trigger_a_notice()
    {
        $wp_api = new TestWPApi();
        
        $old = error_reporting();
        error_reporting(E_ALL);
        
        try {
            $wp_api->cacheGetMultiple(['foo', 'bar']);
            $this->fail("No warning triggered.");
        } catch (Exception $e) {
            $this->assertSame(E_USER_NOTICE, $e->getCode());
            $this->assertStringContainsString(
                'There might be an autoload conflict',
                $e->getMessage()
            );
        }
        finally {
            error_reporting($old);
        }
    }
    
    /** @test */
    public function methods_dont_exist_due_to_autoloading_conflicts_will_be_proxied_to_the_underlying_wordpress_function()
    {
        $user = $this->factory()->user->create_and_get();
        wp_set_current_user($user->ID);
        
        $wp_api = new TestWPApi();
        
        $this->assertSame($user->ID, get_current_user_id());
        $this->assertSame($user->ID, $wp_api->getCurrentUserId());
    }
    
    /** @test */
    public function the_wp_prefix_will_be_prepended_to_non_existing_proxied_functions()
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
    
    /** @test */
    public function mockery_can_scope_the_class()
    {
        $mock = Mockery::mock(TestWPApi::class);
        $mock->shouldReceive('cacheGet')
             ->with('foo')->andReturn(1);
        
        $subject = new ClientClass($mock);
        
        $res = $subject->getSomething('foo');
        
        $this->assertSame(1, $res);
    }
    
    /** @test */
    public function test_doAction()
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
    
    /** @test */
    public function test_applyFilters()
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
    
    /** @test */
    public function test_addAction()
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
    
    /** @test */
    public function test_addFilter()
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
    
    /** @test */
    public function test_isUserLoggedIn()
    {
        $wp = new ScopableWP();
        $this->assertFalse($wp->isUserLoggedIn());
        
        wp_set_current_user(1);
        
        $this->assertTrue($wp->isUserLoggedIn());
        
        wp_set_current_user(0);
        
        $this->assertFalse($wp->isUserLoggedIn());
    }
    
    /** @test */
    public function test_getCurrentUser()
    {
        $user = $this->factory()->user->create_and_get();
        
        $wp = new ScopableWP();
        $this->assertNotEquals($user, $wp->getCurrentUser());
        
        wp_set_current_user($user->ID);
        
        $this->assertEquals($user, $wp->getCurrentUser());
    }
    
    /** @test */
    public function test_caching_methods()
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
    
}
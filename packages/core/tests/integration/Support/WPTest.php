<?php

declare(strict_types=1);

namespace Tests\Core\integration\Support;

use Mockery;
use Snicco\Support\WP;
use Codeception\TestCase\WPTestCase;

use function get_bloginfo;
use function update_option;
use function wp_set_current_user;

class WPTest extends WPTestCase
{
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
    }
    
    /** @test */
    public function reset()
    {
        WP::shouldReceive('wpAdminFolder')->once()->andReturn('bogus');
        $this->assertSame('bogus', WP::wpAdminFolder());
        
        WP::reset();
        
        $this->assertSame('wp-admin', WP::wpAdminFolder());
        Mockery::close();
    }
    
    /** @test */
    public function the_class_can_call_defined_methods_on_the_wordpress_api()
    {
        $admin_url = WP::wpAdminFolder();
        
        $this->assertSame('wp-admin', $admin_url);
    }
    
    /** @test */
    public function the_class_can_be_swapped_for_a_mock()
    {
        WP::shouldReceive('wpAdminFolder')->once()->andReturn('bogus');
        
        $this->assertSame('bogus', WP::wpAdminFolder());
        Mockery::close();
    }
    
    /** @test */
    public function mock_expectations_can_fail()
    {
        try {
            $expectation = WP::shouldReceive('wpAdminFolder')->never();
            $folder = WP::wpAdminFolder();
            Mockery::close();
            $this->fail('Mock didnt fail.');
        } catch (Mockery\CountValidator\Exception  $e) {
            $this->assertStringStartsWith('Method wpAdminFolder', $e->getMessage());
        }
    }
    
    /** @test */
    public function test_partial_mock()
    {
        wp_set_current_user(1);
        
        $mock = WP::partialMock();
        
        $mock->shouldReceive('wpAdminFolder')->once()->andReturn('bogus');
        
        $this->assertSame('bogus', WP::wpAdminFolder());
        $this->assertSame(1, WP::userId());
        
        wp_set_current_user(0);
        
        Mockery::close();
    }
    
    /** @test */
    public function test_spy()
    {
        $spy = WP::spy();
        WP::userIs('editor');
        
        $spy->shouldHaveReceived('userIs')->once()->with('editor');
        
        Mockery::close();
    }
    
    /** @test */
    public function test_spy_can_fail()
    {
        $spy = WP::spy();
        
        WP::userIs('subscriber');
        
        try {
            $spy->shouldHaveReceived('userIs')->once()->with('editor');
            $this->fail("Spy didnt fail.");
        } catch (Mockery\CountValidator\Exception $e) {
            $this->assertStringStartsWith('Method userIs', $e->getMessage());
        }
        Mockery::close();
    }
    
    /** @test */
    public function public_wordpress_methods_can_be_called_dynamically_on_the_wordpress_api()
    {
        update_option('blogdescription', 'my-cool-site');
        
        $this->assertSame('my-cool-site', get_bloginfo('description'));
        $this->assertSame('my-cool-site', WP::get_bloginfo('description'));
    }
    
    /** @test */
    public function test_bad_method_exception()
    {
        $this->expectExceptionMessage("The function [foo] does not exist.");
        WP::foo();
    }
    
    /** @test */
    public function test_mocking_wordpress_functions_that_are_not_on_the_api()
    {
        update_option('blogdescription', 'my-cool-site');
        
        $this->assertSame('my-cool-site', get_bloginfo('description'));
        
        WP::shouldReceive('get_bloginfo')->once()->with('description')->andReturn('foobar');
        
        $this->assertSame('foobar', WP::get_bloginfo('description'));
    }
    
    /** @test */
    public function test_mocking_wordpress_functions_that_are_not_on_the_api_can_fail()
    {
        update_option('blogdescription', 'my-cool-site');
        $this->assertSame('my-cool-site', get_bloginfo('description'));
        
        WP::shouldReceive('get_bloginfo')->once()->with('description')->andReturn('foobar');
        WP::get_bloginfo('description');
        WP::get_bloginfo('description');
        
        try {
            Mockery::close();
            $this->fail("Mockery did not fail");
        } catch (Mockery\CountValidator\Exception $e) {
            $this->assertStringStartsWith("Method get_bloginfo", $e->getMessage());
        }
    }
    
}
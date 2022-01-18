<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP\Tests;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\ScopableWP\WPApi;

/**
 * @api integration tests that load WordPress code.
 */
final class WPApiTest extends TestCase
{
    
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
                "Method [bogus] is not defined on class [Snicco\Component\ScopableWP\Tests\TestWPApi] and neither [\wp_bogus] nor [\bogus] are defined in the global namespace.",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function calling_a_method_via_the_call_magic_method_will_trigger_a_notice()
    {
        $this->expectNotice();
        $this->expectNoticeMessage(
            "Tried to call method [isString] on [Snicco\Component\ScopableWP\Tests\TestWPApi] but its not defined"
        );
        
        $wp_api = new TestWPApi();
        
        $wp_api->isString('foo');
    }
    
}

class TestWPApi extends WPApi
{
    
    public function method1() :string
    {
        return 'method1';
    }
    
}

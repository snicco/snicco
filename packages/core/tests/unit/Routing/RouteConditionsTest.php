<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Conditions\TrueCondition;
use Tests\Core\fixtures\Conditions\FalseCondition;

class RouteConditionsTest extends RoutingTestCase
{
    
    /** @test */
    public function custom_conditions_can_be_added_as_strings()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('false')
                ->handle(fn() => 'foo');
        });
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function custom_conditions_can_be_added_as_objects()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where(new FalseCondition())
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function custom_conditions_can_be_added_before_the_http_verb()
    {
        $this->createRoutes(function () {
            $this->router
                ->where(new TrueCondition())
                ->get()
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', 'foo'));
    }
    
    /** @test */
    public function a_failing_condition_will_not_match_the_route()
    {
        $this->createRoutes(function () {
            $this->router
                ->where(new FalseCondition())
                ->get()
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertEmptyResponse($this->frontendRequest('GET', 'foo'));
    }
    
    /** @test */
    public function a_condition_stack_can_be_added_before_the_http_verb()
    {
        $this->createRoutes(function () {
            $this->router
                ->where(function ($foo) {
                    $GLOBALS['test']['cond1'] = $foo;
                    
                    return $foo === 'foo';
                }, 'foo')
                ->where(function ($bar) {
                    $GLOBALS['test']['cond2'] = $bar;
                    
                    return $bar === 'bar';
                }, 'bar')
                ->get()
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/baz'));
        $this->assertSame('bar', $GLOBALS['test']['cond2']);
        $this->assertSame(
            'foo',
            $GLOBALS['test']['cond1'] ?? null,
            'First condition did not execute'
        );
    }
    
    /** @test */
    public function a_closure_can_be_a_condition()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where(function () {
                    return true;
                })
                ->where(
                    function ($foo, $bar) {
                        return $foo === 'foo' && $bar === 'bar';
                    },
                    'foo',
                    'bar'
                )
                ->handle(
                    function (Request $request, $foo, $bar) {
                        return $foo.$bar;
                    }
                );
            
            $this->router
                ->post()
                ->where(
                    function ($foo, $bar) {
                        return $foo === 'foo' && $bar === 'bar';
                    },
                    'foo',
                    'baz'
                )
                ->handle(
                    function (Request $request, $foo, $bar) {
                        return $foo.$bar;
                    }
                );
            
            $this->router
                ->where(
                    function ($foo, $bar) {
                        return $foo === 'foo' && $bar === 'bar';
                    },
                    'foo',
                    'bar'
                )
                ->put()
                ->handle(
                    function (Request $request, $foo, $bar) {
                        return $foo.$bar;
                    }
                );
        });
        
        $this->assertResponse('foobar', $this->frontendRequest('GET', '/foo'));
        $this->assertResponse('foobar', $this->frontendRequest('PUT', '/foo'));
        
        $this->assertEmptyResponse($this->frontendRequest('POST', 'foo'));
    }
    
    /** @test */
    public function multiple_conditions_can_be_combined_and_all_conditions_have_to_pass()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('true')
                ->where('false')
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertEmptyResponse($this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function a_condition_can_be_negated()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('!false')
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->post()
                ->where('negate', 'false')
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->put()
                ->where('negate', function ($foo) {
                    return $foo !== 'foo';
                }, 'foo')
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/foo'));
        $this->assertResponse('foo', $this->frontendRequest('POST', '/foo'));
        $this->assertResponse('foo', $this->frontendRequest('PUT', '/foo'));
    }
    
    /** @test */
    public function a_condition_can_be_negated_while_passing_arguments()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('maybe', true)
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->post()
                ->where('maybe', false)
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->put()
                ->where('!maybe', false)
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->delete()
                ->where('!maybe', false)
                ->handle(function (Request $request) {
                    return 'foo';
                });
            
            $this->router
                ->patch()
                ->where('!maybe', 'foobar')
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/foo'));
        $this->assertResponse('foo', $this->frontendRequest('PUT', '/foo'));
        $this->assertResponse('foo', $this->frontendRequest('DELETE', '/foo'));
        
        $this->assertEmptyResponse($this->frontendRequest('PATCH', '/foo'));
        $this->assertEmptyResponse($this->frontendRequest('POST', '/foo'));
    }
    
    /** @test */
    public function matching_url_conditions_will_fail_if_custom_conditions_are_not_met()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('maybe', false)
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertEmptyResponse($this->frontendRequest('GET', '/foo'));
        $this->assertTrue($GLOBALS['test']['maybe_condition_run']);
    }
    
    /** @test */
    public function a_condition_object_can_be_negated()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('negate', new FalseCondition())
                ->handle(function (Request $request) {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function failure_of_only_one_condition_leads_to_immediate_rejection_of_the_route()
    {
        $this->createRoutes(function () {
            $this->router
                ->get()
                ->where('false')
                ->where(function () {
                    $this->fail('This condition should not have been called.');
                })
                ->handle(function (Request $request) {
                    $this->fail('This should never be called.');
                });
        });
        
        $this->assertEmptyResponse($this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function conditions_can_be_resolved_using_the_service_container()
    {
        $this->createRoutes(function () {
            $this->router
                ->where('dependency_condition', true)
                ->get('*', function () {
                    return 'foo';
                });
            
            $this->router
                ->where('dependency_condition', false)
                ->post('*', function () {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/foo'));
        $this->assertEmptyResponse($this->frontendRequest('POST', '/foo'));
    }
    
    /** @test */
    public function global_functions_can_be_used_as_custom_conditions()
    {
        $this->createRoutes(function () {
            $this->router->where('is_string', 'foo')
                         ->get('*', function () {
                             return 'foo';
                         });
            
            $this->router
                ->where('is_string', 1)
                ->post('*', function () {
                    return 'foo';
                });
            
            $this->router
                ->where('!is_string', 1)
                ->put('*', function () {
                    return 'foo';
                });
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', '/foo'));
        
        $this->assertResponse('foo', $this->frontendRequest('PUT', '/foo'));
        
        $this->assertEmptyResponse($this->frontendRequest('POST', '/foo'));
    }
    
}
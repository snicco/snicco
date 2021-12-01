<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;

class TrailingSlashTest extends RoutingTestCase
{
    
    /** @test */
    public function routes_can_be_defined_without_leading_slash()
    {
        $this->createRoutes(function () {
            $this->router->get('foo', function () {
                return 'FOO';
            });
        });
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function routes_can_be_defined_with_leading_slash()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'FOO';
            });
        });
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function routes_without_trailing_slash_dont_match_request_with_trailing_slash()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'FOO';
            });
        });
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function routes_with_trailing_slash_match_request_with_trailing_slash()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'FOO';
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/');
        $this->assertResponse('FOO', $request);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function routes_with_trailing_slash_match_request_with_trailing_slash_when_inside_a_group()
    {
        $this->createRoutes(function () {
            $this->router->name('foo')->group(function () {
                $this->router->get('/foo/', function () {
                    return 'FOO';
                });
                
                $this->router->prefix('bar')->group(function () {
                    $this->router->post('/foo/', function () {
                        return 'FOO';
                    });
                });
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/');
        $this->assertResponse('FOO', $request);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertEmptyResponse($request);
        
        $request = $this->frontendRequest('POST', 'https://foobar.com/bar/foo/');
        $this->assertResponse('FOO', $request);
        
        $request = $this->frontendRequest('POST', 'https://foobar.com/bar/foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function routes_with_segments_can_only_match_trailing_slashes()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo/{bar}', function (string $bar) {
                return strtoupper($bar);
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/bar/');
        $this->assertResponse('BAR', $request);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/bar');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function the_router_can_be_forced_to_always_add_trailing_slashes_to_routes()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'FOO';
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo/');
        $this->assertResponse('FOO', $request);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function forced_trailing_slashes_are_not_added_to_file_urls()
    {
        $this->createRoutes(function () {
            $this->router->get('/wp-login.php', function () {
                return 'FOO';
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/wp-login.php');
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function a_route_to_exactly_wp_admin_always_has_the_trailing_slash()
    {
        $this->createRoutes(function () {
            $this->router->get('/wp-admin', function () {
                return 'FOO';
            });
        }, true);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/wp-admin/');
        $this->assertResponse('FOO', $request);
        
        $this->createRoutes(function () {
            $this->router->get('/wp-admin', function () {
                return 'FOO';
            });
        }, false);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/wp-admin/');
        $this->assertResponse('FOO', $request);
        
        $this->createRoutes(function () {
            $this->router->get('/wp-admin/', function () {
                return 'FOO';
            });
        }, false);
        
        $request = $this->frontendRequest('GET', 'https://foobar.com/wp-admin/');
        $this->assertResponse('FOO', $request);
    }
    
    /** @test */
    public function routes_inside_wp_admin_never_have_trailing_slashes_appended()
    {
        // forcing trailing and defined with trailing
        $this->createRoutes(function () {
            $this->router->get('/wp-admin/admin/foo', function () {
                return 'FOO';
            });
        }, true);
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('FOO', $request);
        
        // not-forcing trailing and defined with trailing
        $this->createRoutes(function () {
            $this->router->get('/wp-admin/admin/foo/', function () {
                return 'FOO';
            });
        });
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('FOO', $request);
    }
    
}
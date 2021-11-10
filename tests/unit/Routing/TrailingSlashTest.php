<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Http\Delegate;
use Snicco\Routing\Router;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Tests\helpers\CreatesWpUrls;
use Snicco\Contracts\Middleware;
use Tests\helpers\CreateTestSubjects;
use Psr\Http\Message\ResponseInterface;
use Tests\helpers\CreateDefaultWpApiMocks;
use Snicco\Middleware\Core\OutputBufferMiddleware;
use Snicco\Routing\Conditions\QueryStringCondition;

class TrailingSlashTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    use CreatesWpUrls;
    
    private ContainerAdapter $container;
    
    private Router $router;
    
    /** @test */
    public function routes_can_be_defined_without_leading_slash()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('foo', function () {
                
                return 'FOO';
                
            });
            
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    /** @test */
    public function routes_can_be_defined_with_leading_slash()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'FOO';
                
            });
            
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    /** @test */
    public function routes_without_trailing_slash_dont_match_request_with_trailing_slash()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'FOO';
                
            });
            
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
        $this->runAndAssertEmptyOutput($request);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    /** @test */
    public function routes_with_trailing_slash_match_request_with_trailing_slash()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'FOO';
                
            });
            
        }, true);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
        $this->runAndAssertOutput('FOO', $request);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertEmptyOutput($request);
        
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
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
        $this->runAndAssertOutput('FOO', $request);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertEmptyOutput($request);
        
        $request = TestRequest::fromFullUrl('POST', 'https://foobar.com/bar/foo/');
        $this->runAndAssertOutput('FOO', $request);
        
        $request = TestRequest::fromFullUrl('POST', 'https://foobar.com/bar/foo');
        $this->runAndAssertEmptyOutput($request);
        
    }
    
    /** @test */
    public function routes_with_segments_can_only_match_trailing_slashes()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo/{bar}', function (string $bar) {
                
                return strtoupper($bar);
                
            });
            
        }, true);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/bar/');
        $this->runAndAssertOutput('BAR', $request);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/bar');
        $this->runAndAssertEmptyOutput($request);
        
    }
    
    /** @test */
    public function the_router_can_be_forced_to_always_add_trailing_slashes_to_routes()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'FOO';
                
            });
            
        }, true);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
        $this->runAndAssertOutput('FOO', $request);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
        $this->runAndAssertEmptyOutput($request);
        
    }
    
    /** @test */
    public function forced_trailing_slashes_are_not_added_to_file_urls()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/wp-login.php', function () {
                
                return 'FOO';
                
            });
            
        }, true);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/wp-login.php');
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    /** @test */
    public function a_route_to_exactly_wp_admin_always_has_the_trailing_slash()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/wp-admin', function () {
                
                return 'FOO';
                
            });
            
        }, true);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/wp-admin/');
        $this->runAndAssertOutput('FOO', $request);
        
        $this->createRoutes(function () {
            
            $this->router->get('/wp-admin', function () {
                
                return 'FOO';
                
            });
            
        }, false);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/wp-admin/');
        $this->runAndAssertOutput('FOO', $request);
        
        $this->createRoutes(function () {
            
            $this->router->get('/wp-admin/', function () {
                
                return 'FOO';
                
            });
            
        }, false);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/wp-admin/');
        $this->runAndAssertOutput('FOO', $request);
        
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
        $request = $this->adminRequestTo('foo');
        $this->withoutOutputBufferMiddleware();
        $this->runAndAssertOutput('FOO', $request);
        
        // not-forcing trailing and defined with trailing
        $this->createRoutes(function () {
            
            $this->router->get('/wp-admin/admin/foo/', function () {
                
                return 'FOO';
                
            });
            
        });
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    /** @test */
    public function url_encoded_routes_work()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/german-city/{city}', function (Request $request, string $city) {
                
                return ucfirst($city);
                
            });
            
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/german-city/münchen');
        $this->runAndAssertOutput('München', $request);
        
    }
    
    /** @test */
    public function url_encoded_query_string_conditions_work()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('/foo', function () {
                
                return 'FOO';
                
            })->where(QueryStringCondition::class, ['page' => 'bayern münchen']);
            
        });
        
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo?page=bayern münchen');
        $request = $request->withQueryParams(['page' => 'bayern münchen']);
        $this->runAndAssertOutput('FOO', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newCachedRouteCollection();
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        
    }
    
    protected function beforeTearDown()
    {
        
        Event::setInstance(null);
        Mockery::close();
        WP::reset();
        
    }
    
    private function withoutOutputBufferMiddleware() :void
    {
        $this->container->instance(
            OutputBufferMiddleware::class,
            new class extends Middleware
            {
                
                public function handle(Request $request, Delegate $next) :ResponseInterface
                {
                    return $next($request);
                }
                
            }
        );
    }
    
}
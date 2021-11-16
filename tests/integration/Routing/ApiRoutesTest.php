<?php

declare(strict_types=1);

namespace Tests\integration\Routing;

use Snicco\Events\Event;
use Snicco\Http\Delegate;
use Tests\FrameworkTestCase;
use Snicco\Http\Psr7\Request;
use Snicco\Events\ResponseSent;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class ApiRoutesTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            Event::fake([ResponseSent::class]);
        });
        parent::setUp();
    }
    
    /** @test */
    public function an_api_endpoint_can_be_created_where_all_routes_are_run_on_init_and_shut_down_the_script_afterwards()
    {
        $this->withRequest($this->frontendRequest('GET', 'api-prefix/base/foo'));
        $this->bootApp();
        
        do_action('init');
        
        $response = $this->sentResponse();
        $response->assertOk();
        $response->assertSee('foo endpoint');
        
        // This will shut the script down.
        Event::assertDispatched(function (ResponseSent $event) {
            return $event->request->isWpFrontEnd();
        });
    }
    
    /** @test */
    public function api_routes_only_run_if_the_request_is_an_api_prefix()
    {
        $this->withRequest($this->frontendRequest('GET', 'bogus/base/foo'));
        $this->bootApp();
        
        do_action('init');
        
        $this->assertNoResponse();
        
        // This will shut the script down.
        Event::assertNotDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function api_routes_are_not_loaded_twice_if_the_same_name_is_present()
    {
        $GLOBALS['test']['api_routes'] = false;
        $GLOBALS['test']['other_api_routes'] = false;
        
        $this->withAddedConfig(
            ['routing.definitions' => [ROUTES_DIR, FIXTURES_DIR.DS.'OtherRoutes']]
        );
        $this->withRequest($this->frontendRequest('GET', 'api-prefix/base/foo'));
        $this->bootApp();
        
        do_action('init');
        
        $this->sentResponse()->assertOk()->assertSee('foo endpoint');
        
        $this->assertTrue($GLOBALS['test']['api_routes']);
        $this->assertFalse(
            $GLOBALS['test']['other_api_routes'],
            'Route file with the same name was loaded'
        );
    }
    
    /** @test */
    public function an_api_middleware_group_is_automatically_created()
    {
        $GLOBALS['test']['api_middleware_run'] = false;
        $GLOBALS['test']['api_endpoint_middleware_run'] = false;
        
        $this->withAddedConfig(
            [
                'middleware.groups' => [
                    'api' => [TestApiMiddleware::class],
                ],
            ]
        );
        $this->withRequest($this->frontendRequest('GET', 'api-prefix/base/foo'));
        $this->bootApp();
        
        do_action('init');
        
        $this->sentResponse()->assertSee('foo endpoint');
        
        $this->assertTrue($GLOBALS['test']['api_middleware_run']);
    }
    
    /** @test */
    public function a_fallback_api_route_can_be_defined_that_matches_all_non_existing_endpoints()
    {
        $this->withRequest($this->frontendRequest('GET', 'api-prefix/base/bogus'));
        $this->bootApp();
        
        do_action('init');
        
        $this->sentResponse()->assertStatus(400)->assertSee('The endpoint: bogus does not exist.');
    }
    
    /** @test */
    public function route_files_starting_with_an_underscore_are_not_loaded()
    {
        $this->withRequest($this->frontendRequest('GET', 'api-prefix/base/underscore-api'));
        $this->bootApp();
        
        do_action('init');
        
        $response = $this->sentResponse();
        $response->assertSee('The endpoint: underscore-api does not exist.');
    }
    
}

class TestApiMiddleware extends Middleware
{
    
    public function __construct(ResponseFactory $factory)
    {
        $this->factory = $factory;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $GLOBALS['test']['api_middleware_run'] = true;
        return $next($request);
    }
    
}


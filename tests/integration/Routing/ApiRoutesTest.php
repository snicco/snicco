<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Psr\Http\Message\ResponseInterface;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use BetterWP\Events\Event;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Events\IncomingApiRequest;
    use BetterWP\Events\ResponseSent;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\ResponseFactory;

    class ApiRoutesTest extends TestCase
    {

        protected function setUp() : void
        {
            $this->defer_boot = true;
            $this->afterApplicationCreated(function () {
                Event::fake([ResponseSent::class]);
            });
            parent::setUp();
        }

        /** @test */
        public function an_api_endpoint_can_be_created_where_all_routes_are_run_on_init_and_shut_down_the_script_afterwards()
        {

            $this->withRequest(TestRequest::from('GET', 'api-prefix/base/foo'));
            $this->boot();

            do_action('init');

            $response = $this->sentResponse();
            $response->assertOk();
            $response->assertSee('foo endpoint');

            // This will shut the script down.
            Event::assertDispatched(function (ResponseSent $event) {
                return $event->request->isApiEndPoint();
            });

        }

        /** @test */
        public function api_routes_are_not_loaded_twice_if_the_same_name_is_present()
        {

            $GLOBALS['test']['api_routes'] = false;
            $GLOBALS['test']['other_api_routes'] = false;

            $this->withAddedConfig(['routing.definitions' => [ROUTES_DIR, FIXTURES_DIR.DS.'OtherRoutes']]);
            $this->withRequest(TestRequest::from('GET', 'api-prefix/base/foo'));
            $this->boot();

            do_action('init');

            $this->sentResponse()->assertOk()->assertSee('foo endpoint');

            $this->assertTrue($GLOBALS['test']['api_routes']);
            $this->assertFalse($GLOBALS['test']['other_api_routes'], 'Route file with the same name was loaded');


        }

        /** @test */
        public function a_middleware_group_is_automatically_added()
        {

            $this->withAddedConfig(['middleware.groups' => ['api.test' => [TestApiMiddleware::class]]]);
            $this->withRequest(TestRequest::from('GET', 'api-prefix/base/foo'));
            $this->boot();

            do_action('init');

            $this->sentResponse()->assertForbidden()->assertSee('you cant access this api endpoint.');

        }

        /** @test */
        public function a_fallback_api_route_can_be_defined_that_matches_all_non_existing_endpoints()
        {

            $this->withRequest(TestRequest::from('GET', 'api-prefix/base/bogus'));
            $this->boot();

            do_action('init');

            $this->sentResponse()->assertStatus(400)->assertSee('The endpoint: bogus does not exist.');


        }

    }


    class TestApiMiddleware extends Middleware
    {

        /**
         * @var ResponseFactory
         */
        private $factory;

        public function __construct(ResponseFactory $factory)
        {

            $this->factory = $factory;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            return $this->factory->make(403)
                                 ->html($this->factory->createStream('you cant access this api endpoint.'));
        }

    }
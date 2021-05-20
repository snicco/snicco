<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\stubs\Conditions\IsPost;
    use Tests\traits\AssertsResponse;
    use Tests\traits\CreateWpTestUrls;
    use Tests\UnitTest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\CachedRouteCollection;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\Facade\WpFacade;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    class RouteCachingTest extends UnitTest
    {

        use CreateDefaultWpApiMocks;
        use AssertsResponse;
        use CreateWpTestUrls;

        /**
         * @var Router
         */
        private $router;

        private $route_map_file;

        /**
         * @var UrlGenerator
         */
        private $url_generator;

        /**
         * @var string
         */
        private $route_collection_file;

        /**
         * @var CachedRouteCollection
         */
        private $routes;


        protected function beforeTestRun()
        {

            $this->route_map_file = TESTS_DIR.DS.'_data'.DS.'route.cache.php';
            $this->route_collection_file = TESTS_DIR.DS.'_data'.DS.'route.collection.php';

            $this->newCachedRouter($this->route_map_file, $c = $this->createContainer());
            WpFacade::setFacadeContainer($c);

            $this->assertFalse(file_exists($this->route_map_file));
            $this->assertFalse(file_exists($this->route_collection_file));

        }

        protected function beforeTearDown()
        {

            if (file_exists($this->route_map_file)) {

                unlink($this->route_map_file);
            }
            if (file_exists($this->route_collection_file)) {

                unlink($this->route_collection_file);
            }

            WP::reset();
            Mockery::close();


        }

        private function newCachedRouter($file = null, ContainerAdapter $container = null) : Router
        {

            $container = $container ?? $this->createContainer();

            $condition_factory = new ConditionFactory($this->allConditions(), $container);
            $handler_factory = new RouteActionFactory([], $container);

            $routes = new CachedRouteCollection(
                new CachedFastRouteMatcher($this->createRouteMatcher(), $file ?? $this->route_map_file),
                $condition_factory,
                $handler_factory,
                $this->route_collection_file
            );

            $this->url_generator = new UrlGenerator(new FastRouteUrlGenerator($routes));

            $container->instance(RouteActionFactory::class, $handler_factory);
            $container->instance(ConditionFactory::class, $condition_factory);
            $container->instance(RouteCollection::class, $routes);
            $container->instance(ResponseFactory::class, $response = $this->responseFactory());
            $container->instance(AbstractRouteCollection::class, $routes);

            $this->routes = $routes;

            return $this->router = new Router(
                $container,
                $routes,
                $response
            );


        }

        /** @test */
        public function a_route_can_be_run_when_no_cache_files_exist_yet()
        {

            $this->router->get('foo', Controller::class.'@handle');
            $this->router->loadRoutes();
            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function running_routes_the_first_time_creates_cache_files()
        {

            $this->router->get('foo', Controller::class.'@handle');

            $this->assertFalse(file_exists($this->route_map_file));
            $this->assertFalse(file_exists($this->route_collection_file));

            $this->router->loadRoutes();
            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $this->assertTrue(file_exists($this->route_map_file));
            $this->assertTrue(file_exists($this->route_collection_file));

        }

        /** @test */
        public function routes_can_be_read_from_the_cache_and_match_without_needing_to_define_them()
        {

            // Creates the cache file
            $this->router->get('foo', Controller::class.'@handle');
            $this->router->get('bar', Controller::class.'@handle');
            $this->router->get('baz', Controller::class.'@handle');
            $this->router->get('biz', Controller::class.'@handle');
            $this->router->get('boo', Controller::class.'@handle');
            $this->router->get('teams/{team}', Controller::class.'@handle');
            $this->router->loadRoutes();

            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $router = $this->newCachedRouter();

            $response = $router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $response = $router->runRoute(TestRequest::from('GET', 'bar'));
            $this->assertOutput('foo', $response);

            $response = $router->runRoute(TestRequest::from('GET', 'biz'));
            $this->assertOutput('foo', $response);

            $response = $router->runRoute(TestRequest::from('GET', 'baz'));
            $this->assertOutput('foo', $response);

            $response = $router->runRoute(TestRequest::from('GET', 'boo'));
            $this->assertOutput('foo', $response);

            $router->get('/foobar', Controller::class.'@handle');
            $response = $router->runRoute(TestRequest::from('GET', 'foobar'));
            $this->assertNullResponse($response);

        }

        /** @test */
        public function caching_works_with_closure_routes()
        {

            $class = new Controller();

            $this->router->get('foo', function () use ($class) {

                return $class->handle();

            });

            $this->assertFalse(file_exists($this->route_map_file));
            $this->assertFalse(file_exists($this->route_collection_file));

            $this->router->loadRoutes();

            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));

            $this->assertOutput('foo', $response);

            $this->assertTrue(file_exists($this->route_map_file));
            $this->assertTrue(file_exists($this->route_collection_file));

        }

        /** @test */
        public function a_route_with_conditions_can_be_cached()
        {

            $this->router->get('foo', Controller::class.'@handle')->where('maybe', true);
            $this->router->get('bar', Controller::class.'@handle')->where('maybe', false);
            $this->router->loadRoutes();

            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $response = $this->router->runRoute(TestRequest::from('GET', 'bar'));
            $this->assertNullResponse($response);

            $this->newCachedRouter();

            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $response = $this->router->runRoute(TestRequest::from('GET', 'bar'));
            $this->assertNullResponse($response);


        }

        /** @test */
        public function closure_handlers_are_read_correctly_from_the_cache_file()
        {

            // Create cache file
            $class = new Controller();
            $this->router->get('foo', function () use ($class) {

                return $class->handle();

            });
            $this->router->loadRoutes();
            $response = $this->router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $router = $this->newCachedRouter($this->route_map_file, $this->createContainer());

            $response = $router->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);


        }

        /** @test */
        public function reverse_routing_when_no_cache_file_is_created_yet()
        {

            $this->router->get('foo', Controller::class.'@handle')->name('foo');
            $this->router->loadRoutes();
            $this->assertSame('/foo', $this->url_generator->toRoute('foo', [], false));

        }

        /** @test */
        public function reverse_routing_works_from_the_cache()
        {

            // Create cache
            $this->router->get('foo', Controller::class.'@handle')->name('foo');
            $this->router->get('bar', Controller::class.'@handle')->name('bar');
            $this->router->loadRoutes();

            $this->newCachedRouter();

            $this->assertSame('/foo', $this->url_generator->toRoute('foo', [], false));
            $this->assertSame('/bar', $this->url_generator->toRoute('bar', [], false));


        }

        /** @test */
        public function route_attributes_that_get_changed_after_the_route_got_instantiated_by_the_router_still_get_cached()
        {

            // Create cache
            $this->router->get('foo', Controller::class.'@handle')->name('foo');
            $this->router->get('bar', Controller::class.'@handle')->name('bar');
            $this->router->loadRoutes();

            $this->assertOutput('foo', $this->router->runRoute(TestRequest::from('GET', 'foo')));

            // Cache is loaded into this router instance
            $this->newCachedRouter();

            // This call always happens in the service provider.
            $this->router->loadRoutes();

            $this->assertSame('/foo', $this->url_generator->toRoute('foo', [], false));
            $this->assertSame('/bar', $this->url_generator->toRoute('bar', [], false));

            $this->assertOutput('foo', $this->router->runRoute(TestRequest::from('GET', 'foo')));
            $this->assertOutput('foo', $this->router->runRoute(TestRequest::from('GET', 'bar')));

        }

        /** @test */
        public function cached_routes_work_with_admin_routes()
        {

            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('isAdminAjax')->andReturnFalse();

            // No cache created
            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('admin/foo', function (Request $request, string $page) {

                    return $page;

                });

            });
            $this->router->loadRoutes();
            $request = $this->adminRequestTo('foo');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $this->newCachedRouter();
            $request = $this->adminRequestTo('foo');
            $this->assertOutput('foo', $this->router->runRoute($request));


        }

        /** @test */
        public function cached_routes_work_with_ajax_routes()
        {

            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('isAdminAjax')->andReturnTrue();

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $this->router->loadRoutes();

            $ajax_request = $this->ajaxRequest('foo_action');
            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('FOO_ACTION', $response);

            $this->newCachedRouter();

            $ajax_request = $this->ajaxRequest('foo_action');
            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('FOO_ACTION', $response);


        }

        /** @test */
        public function the_callback_controller_works_with_cached_works () {

            $this->router->get()->where(IsPost::class, true)
                         ->handle(function () {

                             return 'FOO';

                         });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertOutput('FOO', $response);

            $this->newCachedRouter();

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertOutput('FOO', $response);


        }

        /** @test */
        public function a_named_route_with_a_closure_is_deserialized_when_found () {

            // Create cache
            $this->router->get('foo', function () {
                //
            })->name('foo');
            $this->router->loadRoutes();

            $this->newCachedRouter();

            $route = $this->routes->findByName('foo');

            $this->assertInstanceOf(Route::class, $route);

            $this->assertInstanceOf(\Closure::class, $route->getAction());


        }

        private function allConditions() : array
        {

            return array_merge(RoutingServiceProvider::CONDITION_TYPES, [

                'true' => \Tests\stubs\Conditions\TrueCondition::class,
                'false' => \Tests\stubs\Conditions\FalseCondition::class,
                'maybe' => \Tests\stubs\Conditions\MaybeCondition::class,
                'unique' => \Tests\stubs\Conditions\UniqueCondition::class,
                'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

            ]);


        }

    }


    class Controller
    {


        public function handle()
        {

            return 'foo';

        }

    }
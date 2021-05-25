<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\stubs\Conditions\IsPost;
    use Tests\traits\CreateWpTestUrls;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\AbstractFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\CachedRouteCollection;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\FilterWpQuery;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\Facade\WpFacade;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    class RouteCachingTest extends UnitTest
    {

        use CreateDefaultWpApiMocks;
        use TestHelpers;
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

        /**
         * @var ContainerAdapter
         */
        private $container;


        protected function beforeTestRun()
        {

            $this->route_map_file = TESTS_DIR.DS.'_data'.DS.'route.cache.php';
            $this->route_collection_file = TESTS_DIR.DS.'_data'.DS.'route.collection.php';

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

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

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }

        protected function newRouteCollection()
        {

            $condition_factory = new ConditionFactory($this->conditions(), $this->container);
            $handler_factory = new RouteActionFactory([], $this->container);

            $routes = new CachedRouteCollection(
                new CachedFastRouteMatcher($this->createRouteMatcher(), $this->route_map_file),
                $condition_factory,
                $handler_factory,
                $this->route_collection_file
            );

            return $routes;

        }

        private function newCachedRouter()
        {

            $this->routes = $this->newRouteCollection();

            $this->router = $this->newRouter();

        }

        /** @test */
        public function a_route_can_be_run_when_no_cache_files_exist_yet()
        {

            $this->createRoutes(function () {

                $this->router->get('foo', Controller::class.'@handle');


            });

            $this->runAndAssertOutput('foo', $this->webRequest('GET', 'foo'));

        }

        /** @test */
        public function running_routes_the_first_time_creates_cache_files()
        {

            $this->assertFalse(file_exists($this->route_collection_file));
            $this->assertFalse(file_exists($this->route_map_file));

            $this->createRoutes(function () {

                $this->router->get('foo', Controller::class.'@handle');

            });

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            $this->assertTrue(file_exists($this->route_map_file));
            $this->assertTrue(file_exists($this->route_collection_file));

        }

        /** @test */
        public function routes_can_be_read_from_the_cache_and_match_without_needing_to_define_them()
        {

            // Creates the cache file
            $this->createRoutes(function () {

                $this->router->get('foo', Controller::class.'@handle');
                $this->router->get('bar', Controller::class.'@handle');
                $this->router->get('baz', Controller::class.'@handle');
                $this->router->get('biz', Controller::class.'@handle');
                $this->router->get('boo', Controller::class.'@handle');
                $this->router->get('teams/{team}', Controller::class.'@handle');

            });

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            // New route collection from cache;
            $this->newCachedRouter();

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'bar');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'biz');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'baz');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'boo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/teams/dortmund');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function caching_works_with_closure_routes()
        {

            $class = new Controller();

            $this->assertFalse(file_exists($this->route_map_file));
            $this->assertFalse(file_exists($this->route_collection_file));

            $this->createRoutes(function () use ($class) {

                $this->router->get('foo', function () use ($class) {

                    return $class->handle();

                });

            });

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            $this->assertTrue(file_exists($this->route_map_file));
            $this->assertTrue(file_exists($this->route_collection_file));

            $this->newCachedRouter();

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function a_route_with_conditions_can_be_cached()
        {

            $this->createRoutes(function () {

                $this->router->get('foo', Controller::class.'@handle')->where('maybe', true);
                $this->router->get('bar', Controller::class.'@handle')->where('maybe', false);

            });

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'bar');
            $this->runAndAssertEmptyOutput($request);

            $this->newCachedRouter();

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', 'bar');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function a_route_with_wordpress_query_filter_can_be_cached_and_read_from_cache()
        {

            $this->createRoutes(function () {

                $this->router->get('foo')->wpquery(function () {

                    return [
                        'foo' => 'baz',
                    ];

                })->handle(function () {

                    return 'foo';
                });

            });

            $request = TestRequest::from('GET', 'foo');
            $event = new WpQueryFilterable($request, ['foo' => 'bar']);
            $listener = new FilterWpQuery($this->routes);
            $this->assertSame(['foo' => 'baz'], $listener->handle($event));
            $this->runAndAssertOutput('foo', new IncomingWebRequest('wp.php', $request));

            // from cache
            $this->newCachedRouter();

            $event = new WpQueryFilterable(TestRequest::from('GET', 'foo'), ['foo' => 'bar']);
            $listener = new FilterWpQuery($this->routes);
            $this->assertSame(['foo' => 'baz'], $listener->handle($event));
            $this->runAndAssertOutput('foo', new IncomingWebRequest('wp.php', $request));


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
        public function the_callback_controller_works_with_cached_works()
        {

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
        public function a_named_route_with_a_closure_is_deserialized_when_found()
        {

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
<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreatesWpUrls;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Listeners\CreateDynamicHooks;
    use WPEmerge\Support\Arr;

    class RouteRegistrationTest extends IntegrationTest
    {

        use CreatesWpUrls;


        /** @test */
        public function web_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);
            $this->rebindRequest(TestRequest::from('GET', '/foo'));

            ob_start();

            do_action('init');
            apply_filters('template_include', 'wp-template.php');

            $this->assertSame('foo', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

        }

        /** @test */
        public function admin_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            WP::shouldReceive('pluginPageHook')->andReturn('toplevel_page_foo');

            $this->rebindRequest($request = $this->adminRequestTo('foo'));

            ob_start();

            do_action('init');
            IncomingAdminRequest::dispatch([$request]);

            $this->assertSame('FOO_ADMIN', ob_get_clean());

            WP::reset();
            Mockery::close();

        }

        /** @test */
        public function ajax_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);

            WP::shouldReceive('pluginPageHook')->andReturnNull();
            $this->rebindRequest($request = $this->ajaxRequest('foo_action'));

            ob_start();

            do_action('init');
            do_action('admin_init');

            $this->assertSame('FOO_AJAX_ACTION', ob_get_clean());

            WP::reset();
            Mockery::close();

        }


        /** @test */
        public function admin_routes_are_only_run_for_pages_added_with_add_menu_page () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            WP::shouldReceive('pluginPageHook')->andReturnNull();

            $this->rebindRequest($request = $this->adminRequestTo('foo'));

            ApplicationEvent::fake();

            ob_start();

            do_action('init');
            do_action('admin_init');

            ApplicationEvent::assertNotDispatched(CreateDynamicHooks::class);

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

            WP::reset();
            Mockery::close();

        }

        /** @test */
        public function ajax_routes_are_only_run_if_the_request_has_an_action_parameter () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);

            WP::shouldReceive('pluginPageHook')->andReturnNull();

            $request = $this->ajaxRequest('foo_action');

            $this->rebindRequest($request->withParsedBody([]));

            ob_start();

            do_action('init');
            do_action('admin_init');

            $this->assertSame('', ob_get_clean());

            WP::reset();
            Mockery::close();

        }

        /** @test */
        public function the_fallback_route_controller_is_registered_for_web_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
            ]);
            $this->rebindRequest(TestRequest::from('GET', 'post1'));
            $this->makeFallbackConditionPass();
            $this->registerRoutes();

            ob_start();

            apply_filters('template_include', 'wp-template.php');

            $this->assertSame('get_fallback', ob_get_clean());

        }

        /** @test */
        public function the_fallback_controller_does_not_match_admin_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAdminProvider::class,
                ],
            ]);

            $request = TestRequest::from('GET', $this->adminUrlTo('foo'));
            $this->rebindRequest($request);
            $this->makeFallbackConditionPass();
            $this->registerRoutes();

            ob_start();

            IncomingAdminRequest::dispatch([$request]);

            $this->assertSame('', ob_get_clean());

            WP::reset();
            Mockery::close();

        }

        /** @test */
        public function the_fallback_controller_does_not_match_ajax_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAjaxProvider::class,
                ],
            ]);

            $request = TestRequest::from('GET', $this->ajaxUrl('foo'));
            $this->rebindRequest($request);
            $this->makeFallbackConditionPass();
            $this->registerRoutes();

            ob_start();

            IncomingAdminRequest::dispatch([$request]);

            $this->assertSame('', ob_get_clean());

            WP::reset();
            Mockery::close();


        }

        /** @test */
        public function named_groups_prefixes_are_applied_for_admin_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAdminProvider::class,
                ],
            ]);
            $this->registerRoutes();

            $this->assertSame($this->adminUrlTo('foo'), TestApp::routeUrl('admin.foo'));

            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function named_groups_are_applied_for_ajax_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    SimulateAjaxProvider::class,
                ],
            ]);

            $this->registerRoutes();

            $expected = $this->ajaxUrl();

            $this->assertSame($expected, TestApp::routeUrl('ajax.foo'));

            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function custom_routes_dirs_can_be_provided()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    \Tests\fixtures\RoutingDefinitionServiceProvider::class,
                ],
            ]);

            $request = TestRequest::from('GET', 'other');
            $this->rebindRequest($request);
            $this->registerRoutes();

            ob_start();

            apply_filters('template_include', 'wordpress.php');

            $this->assertSame('other', ob_get_clean());


        }

        /** @test */
        public function a_file_with_the_same_name_will_not_be_loaded_twice_for_standard_routes () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ],
                'providers' => [
                    \Tests\fixtures\RoutingDefinitionServiceProvider::class,
                ],
            ]);

            $request = TestRequest::from('GET', 'foo');
            $this->rebindRequest($request);
            $this->registerRoutes();

            ob_start();

            apply_filters('template_include', 'wordpress.php');

            // without the filtering of file names the route in /OtherRoutes/web.php would match
            $this->assertSame('foo', ob_get_clean());


        }



    }

    class SimulateAjaxProvider extends ServiceProvider
    {

        use CreateDefaultWpApiMocks;

        public function register() : void
        {

            $this->createDefaultWpApiMocks();
            WP::shouldReceive('isAdminAjax')->andReturnTrue();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        }

        function bootstrap() : void
        {

        }

    }

    class SimulateAdminProvider extends ServiceProvider
    {

        use CreateDefaultWpApiMocks;
        use CreatesWpUrls;

        public function register() : void
        {

            $this->createDefaultWpApiMocks();

            WP::shouldReceive('isAdminAjax')->andReturnFalse();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });
        }

        function bootstrap() : void
        {

        }

    }
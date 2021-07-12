<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use BetterWP\Support\Arr;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreatesWpUrls;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\Events\IncomingAdminRequest;
    use BetterWP\Events\IncomingAjaxRequest;
    use BetterWP\Support\WP;

    class RouteRegistrationTest extends TestCase
    {

        use CreatesWpUrls;

        protected function setUp() : void
        {
            $this->defer_boot = true;
            parent::setUp();

        }

        protected function tearDown() : void
        {
            parent::tearDown();
        }

        protected function makeFallbackConditionPass () {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;

        }

        /** @test */
        public function web_routes_are_loaded_on_template_include () {

            $this->withRequest(TestRequest::from('GET', '/foo'));
            $this->boot();

            // load routes
            do_action('init');

            apply_filters('template_include', 'wp-template.php');

            $this->sentResponse()->assertSee('foo')->assertOk();

        }

        /** @test */
        public function admin_routes_are_run_on_the_loaded_on_admin_init_hook () {

            $this->withRequest($this->adminRequestTo('foo'));

            $this->withAddedProvider(SimulateAdminProvider::class)
                 ->withoutHooks()
                 ->boot();

            WP::shouldReceive('pluginPageHook')->andReturn('toplevel_page_foo');


            do_action('init');
            do_action('admin_init');
            $hook = WP::pluginPageHook();

            do_action("load-$hook");


            $this->sentResponse()->assertOk()->assertSee('FOO_ADMIN');

        }

        /** @test */
        public function ajax_routes_are_loaded_first_on_admin_init () {

            $this->withRequest($this->ajaxRequest('foo_action'));
            $this->withoutHooks()->boot();

            do_action('init');
            do_action('admin_init');

            $this->sentResponse()->assertOk()->assertSee('FOO_AJAX_ACTION');

        }

        /** @test */
        public function admin_routes_are_also_run_for_other_admin_pages () {

            $request = TestRequest::from('GET', '/wp-admin/index.php')->withLoadingScript('wp-admin/index.php');
            $this->withRequest( $request);

            $this->withAddedProvider(SimulateAdminProvider::class)
                 ->withoutHooks()
                 ->boot();

            WP::shouldReceive('pluginPageHook')->andReturnNull();

            do_action('init');
            do_action('admin_init');
            global $pagenow;
            do_action("load-$pagenow");

            $this->sentResponse()->assertRedirect('/foo');

        }

        /** @test */
        public function ajax_routes_are_only_run_if_the_request_has_an_action_parameter () {

            $this->withRequest( $this->ajaxRequest('foo_action')->withParsedBody([]));
            $this->withoutHooks()->boot();


            do_action('init');
            do_action('admin_init');

            $this->assertNoResponse();

        }

        /** @test */
        public function the_fallback_route_controller_is_registered_for_web_routes()
        {

            $this->withRequest(TestRequest::from('GET', 'post1'));
            $this->boot();
            $this->makeFallbackConditionPass();

            do_action('init');
            apply_filters('template_include', 'wp-template.php');

            $this->sentResponse()->assertSee('get_fallback')->assertOk();

        }

        /** @test */
        public function the_fallback_controller_does_not_match_admin_routes()
        {

            $this->withAddedProvider(SimulateAdminProvider::class)->boot();

            $this->withRequest( $request= $this->adminRequestTo('bogus'));
            $this->makeFallbackConditionPass();

            do_action('init');
            IncomingAdminRequest::dispatch([$request]);

            $this->assertNoResponse();

        }

        /** @test */
        public function the_fallback_controller_does_not_match_ajax_routes()
        {

            $this->withAddedProvider(SimulateAjaxProvider::class);
            $this->withRequest($request = $this->ajaxRequest('bogus'));
            $this->withoutHooks()->boot();
            $this->makeFallbackConditionPass();

            do_action('init');
            IncomingAjaxRequest::dispatch([$request]);

            $this->assertNoResponse();

        }

        /** @test */
        public function named_groups_prefixes_are_applied_for_admin_routes()
        {

            $this->withAddedProvider(SimulateAdminProvider::class);
            $this->boot();

            $this->loadRoutes();

            $this->assertSame('/wp-admin/admin.php?page=foo', TestApp::routeUrl('admin.foo'));

        }

        /** @test */
        public function named_groups_are_applied_for_ajax_routes()
        {
            $this->withAddedProvider(SimulateAjaxProvider::class);
            $this->boot();
            $this->loadRoutes();

            $this->assertSame('/wp-admin/admin-ajax.php', TestApp::routeUrl('ajax.foo'));


        }

        /** @test */
        public function custom_routes_dirs_can_be_provided()
        {

            $request = TestRequest::from('GET', 'other');
            $this->withRequest( $request);
            $this->withAddedProvider(RoutingDefinitionServiceProvider::class)->withoutHooks()->boot();

            do_action('init');
            apply_filters('template_include', 'wordpress.php');

            $this->sentResponse()->assertOk()->assertSee('other');

        }

        /** @test */
        public function a_file_with_the_same_name_will_not_be_loaded_twice_for_standard_routes () {

            $request = TestRequest::from('GET', 'web-other');
            $this->withRequest( $request);
            $this->withAddedProvider(RoutingDefinitionServiceProvider::class)->withoutHooks()->boot();

            do_action('init');
            apply_filters('template_include', 'wordpress.php');

            // without the filtering of file names the route in /OtherRoutes/web.php would match
            $this->assertNoResponse();


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

    class RoutingDefinitionServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $routes = Arr::wrap($this->config->get('routing.definitions'));

            $routes = array_merge($routes, [TESTS_DIR.DS.'fixtures'.DS.'OtherRoutes']);

            $this->config->set('routing.definitions', $routes);

        }

        function bootstrap() : void
        {
        }

    }
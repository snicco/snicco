<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreatesWpUrls;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\Init;
    use WPEmerge\Facade\WP;

    class StandardRoutesTest extends IntegrationTest
    {

        use CreatesWpUrls;

        /** @test */
        public function web_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                    'redirects' => [ROUTES_DIR . DS . 'redirects.php']
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/foo'));

            ob_start();

            apply_filters('template_include', 'wp-template.php');

            $this->assertSame('foo', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

        }

        /** @test */
        public function when_a_web_route_matches_null_is_returned_to_WP_and_the_current_template_is_not_included()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/foo'));

            ob_start();

            $tpl = apply_filters('template_include', 'wp-template.php');

            $this->assertNull($tpl);
            $this->assertSame('foo', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

        }

        /** @test */
        public function the_template_WP_tried_to_load_is_returned_when_no_route_was_found()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/bogus'));

            ob_start();

            $tpl = apply_filters('template_include', 'wp-template.php');

            $this->assertSame('wp-template.php', $tpl);
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();


        }

        /** @test */
        public function admin_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                    'redirects' => [ROUTES_DIR . DS . 'redirects.php']
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            $this->rebindRequest($request = $this->adminRequestTo('foo'));

            ob_start();

            Init::dispatch([$request]);

            $this->assertSame('FOO_ADMIN', ob_get_clean());

            WP::reset();
            \Mockery::close();

        }

        /** @test */
        public function ajax_routes_are_loaded () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                    'redirects' => [ROUTES_DIR . DS . 'redirects.php']
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);

            $this->rebindRequest($request = $this->ajaxRequest('foo_action'));

            ob_start();

            Init::dispatch([$request]);

            $this->assertSame('FOO_AJAX_ACTION', ob_get_clean());

            WP::reset();
            \Mockery::close();

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
<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Session\SessionServiceProvider;

    class LogoutRedirectControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use CreateDefaultWpApiMocks;

        protected function afterSetup()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);


            $this->createDefaultWpApiMocks();
            WP::shouldReceive('checkAdminReferer')
              ->with('log-out')
              ->andReturnTrue()
              ->byDefault();

            WP::shouldReceive('userId')->andReturn(1)->byDefault();

        }

        protected function beforeTearDown()
        {

           WP::reset();
           Mockery::close();
        }

        /** @test */
        public function get_request_to_wp_login_with_are_redirected()
        {



            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=logout');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/auth/logout');



        }

        /** @test */
        public function post_requests_to_wp_login_with_action_are_redirected()
        {


            $request = TestRequest::fromFullUrl('POST', 'https://wpemerge.test/wp-login.php?action=logout');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/auth/logout');


        }

        /** @test */
        public function only_requests_with_action_parameter_are_redirected () {


            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=login');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

        }

        /** @test */
        public function only_requests_with_a_valid_wp_nonce_are_redirected () {



            WP::shouldReceive('checkAdminReferer')->with('log-out')->andReturnUsing(function () {

                throw new \Exception('Wordpress nonce check failed');

            });


            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=logout');
            $this->rebindRequest($request);

            ob_start();

            $this->expectExceptionMessage('Wordpress nonce check failed');

            do_action('init');




        }

        /** @test */
        public function the_redirect_url_is_signed () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            WP::shouldReceive('userId')->andReturn($calvin->ID);
            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=logout');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/auth/logout/'. $calvin->ID);

            $this->logout($calvin);

        }

        /** @test */
        public function a_redirect_url_is_used_if_provided () {



            $calvin = $this->newAdmin();
            $this->login($calvin);

            WP::shouldReceive('userId')->andReturn($calvin->ID);
            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=logout&redirect_to=foobar');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/auth/logout/'. $calvin->ID);
            HeaderStack::assertContains('Location', '&redirect_to=foobar');

            $this->logout($calvin);



        }

        /** @test */
        public function if_no_redirect_url_is_provided_the_user_is_redirect_to_wp_login () {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            WP::shouldReceive('userId')->andReturn($calvin->ID);
            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=logout');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);

            $expected = add_query_arg(
                array(
                    'loggedout' => 'true',
                    'wp_lang'   => get_user_locale( wp_get_current_user()->locale ),
                ),
                wp_login_url()
            );


            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/auth/logout/'. $calvin->ID);
            HeaderStack::assertContains('Location', '&redirect_to='. rawurlencode($expected));

            $this->logout($calvin);

        }



    }
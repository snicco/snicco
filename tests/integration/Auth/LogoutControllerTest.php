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
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Session\SessionServiceProvider;

    class LogoutControllerTest extends IntegrationTest
    {

        use CreateDefaultWpApiMocks;
        use InteractsWithWordpress;

        /**
         * @var UrlGenerator
         */
        private $url;

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

            $this->url = TestApp::url();
            /** @var RouteRegistrar $registrar */
            $registrar = TestApp::resolve(RouteRegistrarInterface::class);
            $registrar->standardRoutes(TestApp::config());
            $registrar->loadIntoRouter();
        }

        protected function beforeTearDown()
        {

            WP::reset();
            Mockery::close();
        }

        /** @test */
        public function the_route_can_not_be_accessed_without_a_valid_signature() {


            $request = TestRequest::from('GET', '/auth/logout/1');
            $this->rebindRequest($request);

            $this->expectException(InvalidSignatureException::class);

            apply_filters('template_include', 'wordpress.php');


        }

        /** @test */
        public function the_route_can_only_be_accessed_if_the_user_id_slot_is_the_user_id_of_the_logged_in_user() {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $john = $this->newAdmin();

            $url = $this->url->signedRoute('auth.logout', ['user_id' => $john->ID] );

            $request = TestRequest::fromFullUrl('GET', $url);
            $this->rebindRequest($request);

            $this->expectException(InvalidSignatureException::class);

            apply_filters('template_include', 'wordpress.php');


            $this->logout($calvin);

        }

        /** @test */
        public function the_current_user_is_logged_out () {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->assertUserLoggedIn($calvin);

            $url = $this->url->signedRoute('auth.logout', ['user_id' => $calvin->ID] );

            $request = TestRequest::fromFullUrl('GET', $url);
            $this->rebindRequest($request);

            ob_start();
            apply_filters('template_include', 'wordpress.php');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location');

            $this->assertUserLoggedOut();

            $this->logout($calvin);

        }

        /** @test */
        public function a_redirect_response_is_returned_with_the_parameter_of_the_query_string () {


            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->assertUserLoggedIn($calvin);

            $url = $this->url->signedRoute('auth.logout', ['user_id' => $calvin->ID, 'query' => ['redirect_to' => '/foo']] );

            $request = TestRequest::fromFullUrl('GET', $url);
            $this->rebindRequest($request);

            ob_start();
            apply_filters('template_include', 'wordpress.php');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/foo');

            $this->assertUserLoggedOut();

            $this->logout($calvin);

        }

        /** @test */
        public function the_user_session_is_destroyed_on_logout () {

            $calvin = $this->newAdmin();
            $this->login($calvin);


            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->testSessionId(), serialize(['foo' => 'bar']));

            $url = $this->url->signedRoute('auth.logout', ['user_id' => $calvin->ID, 'query' => ['redirect_to' => '/foo']] );
            $request = TestRequest::fromFullUrl('GET', $url);
            $request = $request->withAddedHeader('Cookie', 'wp_mvc_session='.$this->testSessionId() );
            $this->rebindRequest($request);

            ob_start();
            apply_filters('template_include', 'wordpress.php');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/foo');

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->testSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is not in the handler anymore
            $data = unserialize($array_handler->read($id_after_login));
            $this->assertNotContains('bar', $data);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->testSessionId()));

            $this->logout($calvin);

        }


    }

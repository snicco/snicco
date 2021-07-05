<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Auth\Controllers\AuthSessionController;
    use BetterWP\Auth\Events\Logout;
    use BetterWP\Auth\Responses\LogoutResponse;
    use BetterWP\Routing\UrlGenerator;
    use BetterWP\ExceptionHandling\Exceptions\InvalidSignatureException;


    /** @see AuthSessionController */
    class AuthSessionControllerLogoutTest extends AuthTestCase
    {

        /**
         * @var UrlGenerator
         */
        private $url;

        protected function setUp() : void
        {

            $this->afterApplicationCreated(function () {

                $this->withoutExceptionHandling();

                $this->url = $this->app->resolve(UrlGenerator::class);

                $this->loadRoutes();

            });
            parent::setUp();
        }

        private function logoutUrl(\WP_User $user, string $redirect_to = null)
        {


            if ($redirect_to) {
                $query = ['user_id' => $user->ID, 'query' => ['redirect_to' => $redirect_to]];
            }
            else {
                $query = ['user_id' => $user->ID];
            }

            return $this->url->signedRoute('auth.logout', $query, 300, true);

        }

        /** @test */
        public function the_route_can_only_be_accessed_if_logged_in () {

            $calvin = $this->createAdmin();

            $this->expectException(InvalidSignatureException::class);

            $response = $this->get($this->logoutUrl($calvin));


        }

        /** @test */
        public function the_route_can_not_be_accessed_without_a_valid_signature()
        {

            $calvin = $this->createAdmin();
            $this->actingAs($calvin);

            $this->expectException(InvalidSignatureException::class);

            $this->get($this->logoutUrl($calvin).'a');

        }

        /** @test */
        public function the_route_can_only_be_accessed_if_the_user_id_segment_is_the_user_id_of_the_logged_in_user()
        {


            $calvin = $this->createAdmin();
            $this->actingAs($calvin);

            $john = $this->createAdmin();

            $this->expectException(InvalidSignatureException::class);

            $this->get($this->logoutUrl($john));


        }

        /** @test */
        public function the_current_user_is_logged_out()
        {

            ApplicationEvent::fake([Logout::class]);
            $this->actingAs($calvin = $this->createAdmin());
            $this->assertAuthenticated($calvin);

            $auth_cookie_cleared = false;
            add_action('clear_auth_cookie', function () use (&$auth_cookie_cleared) {

                $auth_cookie_cleared = true;

            });

            $response = $this->get($this->logoutUrl($calvin));

            $response->assertStatus(302);
            $response->assertRedirectToRoute('home');
            $this->assertNotAuthenticated($calvin);
            $this->assertTrue($auth_cookie_cleared);
            $this->assertSessionUserId(0);
            ApplicationEvent::assertDispatched(function (Logout $event ) use ($calvin) {
                return $event->user_id = $calvin->ID;
            });
        }

        /** @test */
        public function a_redirect_response_is_returned_with_the_parameter_of_the_query_string()
        {


            $this->actingAs($calvin = $this->createAdmin());

            $url = $this->logoutUrl($calvin, '/foo');

            $this->get($url)->assertRedirect('/foo');

            $this->assertNotAuthenticated($calvin);


        }

        /** @test */
        public function the_user_session_is_destroyed_on_logout()
        {

            $this->withDataInSession(['foo' => 'bar'], $id_before_logout = $this->testSessionId());
            $this->withSessionCookie();
            $this->actingAs($calvin = $this->createAdmin());

            $response = $this->get($this->logoutUrl($calvin));

            $response->assertRedirectToRoute('home', 302);
            $response->assertInstance(LogoutResponse::class);

            $id_after_logout = $this->session->getId();

            // Session Id not the same
            $this->assertNotSame($id_before_logout, $id_after_logout);

            $response->cookie('wp_mvc_session')->assertValue($id_after_logout);

            $this->assertDriverEmpty($id_before_logout);
            $this->assertDriverEmpty($id_after_logout);

        }


    }

<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Authenticators;

    use Tests\AuthTestCase;
    use WPMvc\Auth\Authenticators\MagicLinkAuthenticator;
    use WPMvc\Auth\Exceptions\FailedAuthenticationException;
    use WPMvc\Auth\Responses\MagicLinkLoginView;
    use WPMvc\Contracts\MagicLink;
    use WPMvc\Routing\UrlGenerator;

    class MagicLinkAuthenticatorTest extends AuthTestCase
    {

        /**
         * @var UrlGenerator
         */
        private $url;

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withReplacedConfig('auth.through', [
                    MagicLinkAuthenticator::class,
                ]);

                $this->withReplacedConfig('auth.authenticator', 'email');
                $this->withReplacedConfig('auth.primary_view', MagicLinkLoginView::class);

            });

            $this->afterApplicationCreated(function () {

                $this->url = $this->app->resolve(UrlGenerator::class);
                $this->loadRoutes();

            });

            parent::setUp();
        }

        private function routeUrl(int $user_id) : string
        {

            return $this->url->signedRoute('auth.login.magic-link', ['query' => ['user_id' => $user_id]], 300, true);
        }

        /** @test */
        public function an_invalid_magic_link_will_fail()
        {

            $this->withoutExceptionHandling();

            $calvin = $this->createAdmin();

            $url = $this->routeUrl($calvin->ID);

            $this->expectException(FailedAuthenticationException::class);

            $this->get($url.'a');

        }

        /** @test */
        public function a_magic_link_for_a_non_resolvable_user_will_fail()
        {

            $this->withoutExceptionHandling();

            $calvin = $this->createAdmin();

            $url = $this->routeUrl($calvin->ID + 1000);

            $this->expectException(FailedAuthenticationException::class);

            $this->get($url);


        }

        /** @test */
        public function a_valid_link_will_log_the_user_in()
        {

            $this->withAddedConfig('auth.features.remember_me', 10);

            $calvin = $this->createAdmin();
            $url = $this->routeUrl($calvin->ID);

            $this->assertNotAuthenticated($calvin);

            $response = $this->get($url);

            $response->assertRedirectToRoute('dashboard');
            $this->assertAuthenticated($calvin);
            $this->assertTrue($this->session->hasRememberMeToken());
            $this->assertSame([], $this->app->resolve(MagicLink::class)
                                            ->getStored(), 'Auth magic link not deleted.');


        }

        /** @test */
        public function failed_attempts_are_redirected_to_the_login_route()
        {

            $calvin = $this->createAdmin();

            $url = $this->routeUrl($calvin->ID);

            $this->followingRedirects();
            $response = $this->get($url.'a');

            $response->assertOk()
                     ->assertSee('Your login link either expired or is invalid. Please request a new one.');

        }


    }
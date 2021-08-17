<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Authenticators;

    use Tests\AuthTestCase;
    use Snicco\Contracts\MagicLink;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Auth\Responses\MagicLinkLoginView;
    use Snicco\Auth\Authenticators\MagicLinkAuthenticator;
    use Snicco\Auth\Exceptions\FailedAuthenticationException;

    class MagicLinkAuthenticatorTest extends AuthTestCase
    {
    
        private UrlGenerator $url;
    
        protected function setUp() :void
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

            $this->withAddedConfig('auth.features.remember_me', true);

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
        public function failed_attempts_return_a_302_redirect_to_the_login_view()
        {
        
            $this->followingRedirects();
            $calvin = $this->createAdmin();
        
            $url = $this->routeUrl($calvin->ID);
    
            $response = $this->get($url.'a');
    
            $response->assertSee(
                'Your magic link is either invalid or expired. Please request a new one.'
            );
    
            $this->assertGuest();
    
        }
    
        /** @test */
        public function invalid_magic_link_attempts_throw_an_failed_authentication_exception()
        {
        
            $this->withoutExceptionHandling();
        
            $calvin = $this->createAdmin();
        
            $url = $this->routeUrl($calvin->ID + 1000);
        
            $exception_caught = false;
        
            try {
            
                $this->get($url);
            } catch (FailedAuthenticationException $e) {
            
                $this->assertStringStartsWith('Failed authentication', $e->fail2BanMessage());
                $exception_caught = true;
            
            }
        
            $this->assertTrue($exception_caught);
        
        }
    
    }
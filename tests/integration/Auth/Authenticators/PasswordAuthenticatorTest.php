<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Authenticators;

    use Snicco\Auth\Authenticators\PasswordAuthenticator;
    use Snicco\Routing\UrlGenerator;
    use Tests\AuthTestCase;

    class PasswordAuthenticatorTest extends AuthTestCase
    {

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withReplacedConfig('auth.through', [
                    PasswordAuthenticator::class,
                ]);
            });

            $this->afterApplicationCreated(function () {

                $this->url = $this->app->resolve(UrlGenerator::class);
                $this->loadRoutes();

            });

            parent::setUp();
        }

        /** @test */
        public function missing_password_or_login_fails()
        {

            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'pwd' => 'password',
                ]);

            $response->assertRedirectToRoute('auth.login')
                     ->assertSessionHasErrors(['message' => 'Your password or username is not correct.']);

            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'log' => 'admin',
                ]);

            $response->assertRedirectToRoute('auth.login')
                     ->assertSessionHasErrors(['message' => 'Your password or username is not correct.']);

            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'log' => '',
                    'pwd' => '',
                ]);

            $response->assertRedirectToRoute('auth.login')
                     ->assertSessionHasErrors(['message' => 'Your password or username is not correct.']);


        }

        /** @test */
        public function a_non_resolvable_user_throws_an_exception()
        {

            $login_failed_fired = false;
            add_action('wp_login_failed', function () use (&$login_failed_fired) {

                $login_failed_fired = true;

            });

            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'log' => 'calvin',
                    'pwd' => 'password',
                ]
            );

            $response->assertRedirectToRoute('auth.login')
                     ->assertSessionHasErrors(['message' => 'Your password or username is not correct.'])
                     ->assertSessionHasInput(['log' => 'calvin']);

            $this->assertTrue($login_failed_fired);

        }

        /** @test */
        public function a_user_can_login_with_valid_credentials()
        {

            $this->withAddedConfig('auth.features.remember_me', true);

            $calvin = $this->createAdmin();


            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'log' => $calvin->user_login,
                    'pwd' => 'password',
                    'remember_me' => '1',
                ]
            );

            $response->assertRedirectToRoute('dashboard');
            $this->assertAuthenticated($calvin);
            $this->assertTrue($this->session->hasRememberMeToken());


        }

        /** @test */
        public function a_user_can_login_with_his_email_address_instead_of_the_username()
        {

            $this->withAddedConfig('auth.features.remember_me', true);

            $calvin = $this->createAdmin();

            $token = $this->withCsrfToken();

            $response = $this->post('/auth/login', $token +
                [
                    'log' => $calvin->user_email,
                    'pwd' => 'password',
                    'remember_me' => '1',
                ]
            );

            $response->assertRedirectToRoute('dashboard');
            $this->assertAuthenticated($calvin);
            $this->assertTrue($this->session->hasRememberMeToken());

        }

    }
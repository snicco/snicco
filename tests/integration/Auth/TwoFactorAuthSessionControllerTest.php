<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\helpers\HashesSessionIds;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class TwoFactorAuthSessionControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use HashesSessionIds;

        private $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
                'lifetime' => 3600
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
            'auth' => [

                'confirmation' => [
                    'duration' => 10
                ],

                'remember' => [
                    'enabled' => false,
                ],
                'features' => [
                    'two-factor-authentication' => true
                ]
            ]
        ];

        /** @test */
        public function the_auth_challenge_is_not_rendered_if_a_user_is_not_challenged () {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = TestRequest::from('GET', '/auth/two-factor/challenge');

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/auth/login');


        }

        /** @test */
        public function the_route_cant_be_accessed_if_2fa_is_disabled () {

            Arr::set($this->config, 'auth.features.two-factor-authentication', false);
            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = TestRequest::from('GET', '/auth/two-factor/challenge');

            $this->assertOutput('', $request);
            HeaderStack::assertHasNone();

        }

        /** @test */
        public function the_auth_challenge_is_not_rendered_if_a_user_doesnt_use_2fa_auth () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = TestRequest::from('GET', '/auth/two-factor/challenge');
            $request = $this->withSession($request, ['2fa.challenged_user' => $calvin->ID]);

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertHas('Location', '/auth/login');


        }

        /** @test */
        public function the_2fa_challenge_screen_gets_rendered () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $request = TestRequest::from('GET', '/auth/two-factor/challenge');
            $request = $this->withSession($request, ['2fa.challenged_user' => $calvin->ID]);

            $this->assertOutputContains('Code', $request);
            HeaderStack::assertHasStatusCode(200);

        }

    }
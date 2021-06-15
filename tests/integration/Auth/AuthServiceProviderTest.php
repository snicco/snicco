<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class AuthServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function the_auth_views_are_bound_in_the_config () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    AuthServiceProvider::class,
                    SessionServiceProvider::class
                ],
            ]);

            $views = TestApp::config('views');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'views';

            $this->assertContains($expected, $views);


        }

        /** @test */
        public function middleware_aliases_are_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    AuthServiceProvider::class,
                    SessionServiceProvider::class
                ],
            ]);

            $middleware_aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('auth.confirmed', $middleware_aliases);
            $this->assertArrayHasKey('auth.unconfirmed', $middleware_aliases);

        }

        /** @test */
        public function the_auth_routes_are_bound_in_the_config()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class
                ],
            ]);

            $routes = TestApp::config('routing.definitions');
            $expected = ROOT_DIR.DS.'src'.DS.'Auth'.DS.'routes';

            $this->assertContains($expected, $routes);

        }

        /** @test */
        public function all_controllers_are_bound () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class
                ],
            ]);

            $this->assertInstanceOf(ConfirmAuthMagicLinkController::class, TestApp::resolve(ConfirmAuthMagicLinkController::class));
            $this->assertInstanceOf(ForgotPasswordController::class, TestApp::resolve(ForgotPasswordController::class));
            $this->assertInstanceOf(ResetPasswordController::class, TestApp::resolve(ResetPasswordController::class));

        }

        /** @test */
        public function the_wp_login_logout_events_from_the_session_package_are_unset () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                    AuthServiceProvider::class
                ],
            ]);


            $listeners = TestApp::config('events.listeners');

            $login = $listeners[NewLogin::class] ?? [];
            $logout = $listeners[NewLogout::class] ?? [];

            $this->assertNotContains([SessionManager::class, 'migrateAfterLogin'], $login);
            $this->assertNotContains([SessionManager::class, 'invalidateAfterLogout'], $logout);

        }

    }
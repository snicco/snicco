<?php


    declare(strict_types = 1);


    namespace Tests;

    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Validation\ValidationServiceProvider;


    class AuthTestCase extends TestCase
    {

        /**
         * @var AuthSessionManager
         */
        protected $session_manager;

        public function packageProviders() : array
        {

            return [
                ValidationServiceProvider::class,
                SessionServiceProvider::class,
                AuthServiceProvider::class
            ];
        }

        // We have to refresh the session manager because otherwise we would always operate on the same
        // instance of Session:class
        protected function refreshSessionManager()
        {
            $this->instance(SessionManagerInterface::class,
                $m = new AuthSessionManager(
                    $this->app->resolve(SessionManager::class),
                    $this->app->resolve(SessionDriver::class),
                    $this->config->get('auth')
                )
            );

            $this->session_manager = $m;
        }

        protected function tearDown() : void
        {

            $this->logout();
            parent::tearDown();
        }



    }
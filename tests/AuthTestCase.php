<?php


    declare(strict_types = 1);


    namespace Tests;

    use Illuminate\Support\Collection;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\AuthSessionManager;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Encryptor;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Validation\ValidationServiceProvider;


    class AuthTestCase extends TestCase
    {

        /**
         * @var AuthSessionManager
         */
        protected $session_manager;

        /**
         * @var array
         */
        protected $codes;

        /**
         * @var Encryptor
         */
        protected $encryptor;

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

        protected function without2Fa()
        {

            $this->withReplacedConfig('auth.features.2fa', false);

            return $this;
        }

        protected function with2Fa()
        {

            $this->withReplacedConfig('auth.features.2fa', true);

            return $this;
        }

        protected function createCodes() : array
        {

            return Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();

        }

        protected function encryptCodes(array $codes) : string
        {

            return $this->encryptor->encrypt(json_encode($codes));

        }

        protected function getUserRecoveryCodes(\WP_User $user) {

            $codes = get_user_meta($user->ID, 'two_factor_recovery_codes', true);

            if ( $codes === '') {
                return $codes;
            }

            $codes = json_decode($this->encryptor->decrypt($codes), true);
            return $codes;
        }

        protected function getUserSecret(\WP_User $user) {

            $secret =  get_user_meta($user->ID, 'two_factor_secret', true);
            return $secret;
        }

    }
<?php


    declare(strict_types = 1);


    namespace Tests;

    use Illuminate\Support\Collection;
    use Snicco\Auth\AuthServiceProvider;
    use Snicco\Auth\AuthSessionManager;
    use Snicco\Auth\RecoveryCode;
    use Snicco\Session\Contracts\SessionDriver;
    use Snicco\Session\Contracts\SessionManagerInterface;
    use Snicco\Session\Encryptor;
    use Snicco\Session\SessionManager;
    use Snicco\Session\SessionServiceProvider;
    use Snicco\Validation\ValidationServiceProvider;
    use WP_User;

    class AuthTestCase extends TestCase
    {

        protected AuthSessionManager $session_manager;
        protected array $codes;
        protected Encryptor $encryptor;
        protected string $valid_one_time_code = '123456';
        protected string $invalid_one_time_code = '111111';

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

        protected function without2Fa() :self
        {

            $this->withReplacedConfig('auth.features.2fa', false);

            return $this;
        }

        protected function with2Fa() :self
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

        protected function getUserRecoveryCodes(WP_User $user) {

            $codes = get_user_meta($user->ID, 'two_factor_recovery_codes', true);

            if ( $codes === '') {
                return $codes;
            }

            return json_decode($this->encryptor->decrypt($codes), true);
        }

        protected function getUserSecret(WP_User $user) {

            return get_user_meta($user->ID, 'two_factor_secret', true);
        }

        protected function authenticateAndUnconfirm(WP_User $user)
        {
            $this->actingAs($user);
            $this->travelIntoFuture(10);
        }

    }
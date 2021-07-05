<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Authenticators;

    use BetterWP\Auth\Contracts\Authenticator;
    use BetterWP\Auth\Contracts\TwoFactorAuthenticationProvider;
    use BetterWP\Auth\Exceptions\FailedTwoFactorAuthenticationException;
    use BetterWP\Auth\RecoveryCode;
    use BetterWP\Auth\Traits\PerformsTwoFactorAuthentication;
    use BetterWP\Auth\Traits\ResolveTwoFactorSecrets;
    use BetterWP\Auth\Traits\ResolvesUser;
    use BetterWP\Contracts\EncryptorInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

    class TwoFactorAuthenticator extends Authenticator
    {

        use ResolvesUser;
        use PerformsTwoFactorAuthentication;

        /**
         * @var TwoFactorAuthenticationProvider
         */
        private $provider;

        /**
         * @var EncryptorInterface
         */
        private $encryptor;

        protected $failure_message = 'Invalid Code provided.';

        /**
         * @var array
         */
        private $recovery_codes = [];

        public function __construct(TwoFactorAuthenticationProvider $provider, EncryptorInterface $encryptor)
        {
            $this->provider = $provider;
            $this->encryptor = $encryptor;
        }

        public function attempt(Request $request, $next) : Response
        {

            $session = $request->session();
            $challenged_user_id = $session->challengedUser();

            if ( ! $challenged_user_id || ! $this->userHasTwoFactorEnabled($user = $this->getUserById($challenged_user_id))) {

                return $next($request);

            }

            $valid = $this->validateTwoFactorAuthentication($this->provider, $request, $challenged_user_id);

            if ( ! $valid) {

                throw new FailedTwoFactorAuthenticationException($this->failure_message, $request);

            }

            $remember = $session->get('auth.2fa.remember', false);
            $session->forget('auth.2fa');

            return $this->login($user, $remember);

        }


    }
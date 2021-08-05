<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Authenticators;

    use Snicco\Auth\Contracts\Authenticator;
    use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
    use Snicco\Auth\Exceptions\FailedTwoFactorAuthenticationException;
    use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Contracts\EncryptorInterface;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;

    class TwoFactorAuthenticator extends Authenticator
    {

        use ResolvesUser;
        use PerformsTwoFactorAuthentication;

        private TwoFactorAuthenticationProvider $provider;

        private EncryptorInterface $encryptor;

        protected string $failure_message = 'Invalid Code provided.';

        private array $recovery_codes = [];

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
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Exceptions\FailedTwoFactorAuthenticationException;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Auth\Traits\PerformsTwoFactorAuthentication;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class TwoFactorAuthenticator extends Authenticator
    {

        use ResolveTwoFactorSecrets;
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

            if ( ! $session->challengedUser()) {

                return $next($request);

            }

            $user_id = $session->challengedUser();

            $valid = $this->validateTwoFactorAuthentication($request, $user_id);

            if ( ! $valid ) {

                throw new FailedTwoFactorAuthenticationException($this->failure_message, $request);

            }

            $remember = $session->get('2fa.remember');
            $session->forget('2fa');

            return $this->login($this->getUserById($user_id), $remember);

        }


    }
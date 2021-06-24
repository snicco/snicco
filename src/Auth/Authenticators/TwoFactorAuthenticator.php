<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Exceptions\FailedTwoFactorAuthenticationException;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Auth\ResolveTwoFactorSecrets;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class TwoFactorAuthenticator extends Authenticator
    {

        use ResolveTwoFactorSecrets;
        use ResolvesUser;

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

            if ( ! $session->challengedUser() ) {

                return $next($request);

            }

            $user_id = $session->challengedUser();

            if ( $code = $this->validRecoveryCode($request, $user_id) ) {

                $this->replaceRecoveryCode($code,$user_id);

            }
            elseif ( ! $this->hasValidOneTimeCode($request, $user_id) ) {

                throw new FailedTwoFactorAuthenticationException($this->failure_message, $request);

            }

            $remember = $session->get('2fa.remember');
            $session->forget('2fa');

            return $this->login($this->getUserById($user_id),$remember );

        }

        private function validRecoveryCode(Request $request, $user_id)
        {

            $provided_code = $request->input('recovery-code', '');

            $this->recovery_codes = json_decode( $this->encryptor->decrypt($this->recoveryCodes( $user_id )), true );

            return collect( $this->recovery_codes )->first(function ($code) use ($provided_code) {
                return hash_equals($provided_code, $code) ? $code : null;
            });

        }

        private function replaceRecoveryCode($code, int $user_id)
        {

            $new_codes = str_replace($code, RecoveryCode::generate(), $this->recovery_codes);

            $new_codes = $this->encryptor->encrypt(json_encode($new_codes));

            $this->updateRecoveryCodes($user_id, $new_codes);

        }

        private function hasValidOneTimeCode(Request $request, int $user_id) : bool
        {
            $token = $request->input('token', '');
            $user_secret = $this->twoFactorSecret($user_id);

            return $this->provider->verify($user_secret, $token);

        }

    }
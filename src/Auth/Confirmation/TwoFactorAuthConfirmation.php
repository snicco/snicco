<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Confirmation;

    use WP_User;
    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\PerformsTwoFactorAuthentication;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;

    class TwoFactorAuthConfirmation implements AuthConfirmation
    {

        use ResolveTwoFactorSecrets;
        use PerformsTwoFactorAuthentication;

        /**
         * @var AuthConfirmation
         */
        private $fallback;

        /**
         * @var WP_User
         */
        private $current_user;

        /**
         * @var string
         */
        private $user_secret;

        /**
         * @var TwoFactorAuthenticationProvider
         */
        private $provider;

        /**
         * @var ResponseFactory
         */
        private $response_factory;
        /**
         * @var EncryptorInterface
         */
        private $encryptor;

        public function __construct(
            AuthConfirmation $fallback,
            TwoFactorAuthenticationProvider $provider,
            ResponseFactory $response_factory,
            EncryptorInterface $encryptor,
            WP_User $current_user
        )
        {

            $this->fallback = $fallback;
            $this->current_user = $current_user;
            $this->user_secret = $this->twoFactorSecret($this->current_user->ID);
            $this->provider = $provider;
            $this->response_factory = $response_factory;
            $this->encryptor = $encryptor;

        }

        public function prepare(Request $request) : AuthConfirmation
        {

            if ( ! $this->userHasTwoFactorAuthEnabled() ) {
                $this->fallback->prepare($request);
            }

            return $this;

        }

        public function confirm(Request $request)
        {

            if ( ! $this->userHasTwoFactorAuthEnabled() ) {
                return $this->fallback->confirm($request);
            }

            $valid = $this->validateTwoFactorAuthentication($request, $this->current_user->ID);

            if ( $valid === true ) {
                return true;
            }

            return ['message' => 'Invalid code provided'];

        }

        public function viewResponse(Request $request) : Response
        {

            if ( ! $this->userHasTwoFactorAuthEnabled()) {

                return $this->fallback->viewResponse($request);

            }

            return $this->response_factory->view('auth-parent', [
                'view' => 'auth-two-factor-challenge',
                'post_to' => $request->path(),
            ]);


        }

        private function userHasTwoFactorAuthEnabled() :bool
        {
            return $this->user_secret !== '';
        }

    }
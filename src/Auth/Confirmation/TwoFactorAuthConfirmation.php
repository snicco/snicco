<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Confirmation;

    use WP_User;
    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
    use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
    use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Auth\Traits\ResolveTwoFactorSecrets;
    use Snicco\Contracts\EncryptorInterface;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;
    use Snicco\Http\ResponseFactory;

    class TwoFactorAuthConfirmation implements AuthConfirmation
    {

        use PerformsTwoFactorAuthentication;
        use InteractsWithTwoFactorSecrets;

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

        public function confirm(Request $request)
        {

            if ( ! $this->userHasTwoFactorEnabled($request->user()) ) {
                return $this->fallback->confirm($request);
            }

            $valid = $this->validateTwoFactorAuthentication($this->provider, $request, $request->userId());

            if ( $valid === true ) {
                return true;
            }

            return ['message' => 'Invalid code provided.'];

        }

        public function viewResponse(Request $request)
        {

            if ( ! $this->userHasTwoFactorEnabled($request->user()) ) {

                return $this->fallback->viewResponse($request);

            }

            return $this->response_factory->view('auth-layout', [
                'view' => 'auth-two-factor-challenge',
                'post_to' => $request->path(),
            ]);


        }


    }
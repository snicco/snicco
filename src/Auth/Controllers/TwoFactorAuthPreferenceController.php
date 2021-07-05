<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Controllers;

    use WPMvc\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPMvc\Auth\Traits\GeneratesRecoveryCodes;
    use WPMvc\Auth\Traits\InteractsWithTwoFactorCodes;
    use WPMvc\Auth\Traits\InteractsWithTwoFactorSecrets;
    use WPMvc\Auth\Traits\ResolvesUser;
    use WPMvc\Auth\Traits\ResolveTwoFactorSecrets;
    use WPMvc\Contracts\EncryptorInterface;
    use WPMvc\Http\Controller;
    use WPMvc\Http\Psr7\Request;

    class TwoFactorAuthPreferenceController extends Controller
    {

        use ResolvesUser;
        use InteractsWithTwoFactorSecrets;
        use InteractsWithTwoFactorCodes;

        /**
         * @var TwoFactorAuthenticationProvider
         */
        private $provider;

        /**
         * @var EncryptorInterface
         */
        private $encryptor;

        public function __construct(TwoFactorAuthenticationProvider $provider, EncryptorInterface $encryptor)
        {

            $this->provider = $provider;
            $this->encryptor = $encryptor;

        }

        public function store(Request $request)
        {

            $id = $request->userId();

            if ($this->userHasTwoFactorEnabled($user = $this->getUserById($id))) {

                return $this->response_factory->json(
                    [
                        'message' => 'Two-Factor authentication is already enabled.',
                    ],
                    409);

            }


            $this->saveSecret($user->ID, $this->provider->generateSecretKey());
            $this->saveCodes($user->ID, $backup_codes = $this->generateNewRecoveryCodes());

            return $this->response_factory->json($backup_codes);

        }

        public function destroy(Request $request)
        {

            $id = $request->userId();

            if ( ! $this->userHasTwoFactorEnabled($user = $this->getUserById($id))) {

                return $this->response_factory->json([
                    'message' => 'Two-Factor authentication is not enabled.',
                ], 409);

            }

            $this->disableTwoFactorAuthentication($user->ID);


            return $this->response_factory->noContent();


        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\GeneratesRecoveryCodes;
    use WPEmerge\Auth\Traits\InteractsWithTwoFactorCodes;
    use WPEmerge\Auth\Traits\InteractsWithTwoFactorSecrets;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

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
<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Controllers;

    use Snicco\Http\Controller;
    use Snicco\Http\Psr7\Request;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Contracts\EncryptorInterface;
    use Snicco\Auth\Traits\InteractsWithTwoFactorCodes;
    use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
    use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

    class TwoFactorAuthPreferenceController extends Controller
    {
	
	    use ResolvesUser;
	    use InteractsWithTwoFactorSecrets;
	    use InteractsWithTwoFactorCodes;
	
	    private TwoFactorAuthenticationProvider $provider;
	    private EncryptorInterface              $encryptor;

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
<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Controllers;

    use BetterWP\Auth\Traits\InteractsWithTwoFactorCodes;
    use BetterWP\Auth\Traits\InteractsWithTwoFactorSecrets;
    use BetterWP\Auth\Traits\ResolvesUser;
    use BetterWP\Contracts\EncryptorInterface;
    use BetterWP\Http\Controller;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

    class RecoveryCodeController extends Controller
    {

        use InteractsWithTwoFactorCodes;
        use InteractsWithTwoFactorSecrets;
        use ResolvesUser;

        /**
         * @var EncryptorInterface
         */
        private $encryptor;

        public function __construct(EncryptorInterface $encryptor)
        {
            $this->encryptor = $encryptor;
        }

        public function index(Request $request) : Response
        {

            if ( ! $this->userHasTwoFactorEnabled($this->getUserById($id = $request->userId()))) {

                return $this->response_factory->json([
                    'message' => 'Two factor authentication is not enabled.',
                ], 409);

            }

            return $this->response_factory->json($this->recoveryCodes($id));

        }

        public function update(Request $request)
        {

            if ( ! $this->userHasTwoFactorEnabled($this->getUserById($id = $request->userId()))) {

                return $this->response_factory->json([
                    'message' => 'Two factor authentication is not enabled.',
                ], 409);

            }

            $codes = $this->generateNewRecoveryCodes();
            $this->saveCodes($id, $codes);

            return $this->response_factory->json($codes);


        }


    }
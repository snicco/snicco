<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Controllers;

    use Snicco\Auth\Traits\InteractsWithTwoFactorCodes;
    use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Contracts\EncryptorInterface;
    use Snicco\Http\Controller;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;

    class RecoveryCodeController extends Controller
    {

        use InteractsWithTwoFactorCodes;
        use InteractsWithTwoFactorSecrets;
        use ResolvesUser;

        private EncryptorInterface $encryptor;

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
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Auth\Traits\DecryptsRecoveryCodes;
    use WPEmerge\Auth\Traits\GeneratesRecoveryCodes;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class RecoveryCodeController extends Controller
    {

        use ResolveTwoFactorSecrets;
        use DecryptsRecoveryCodes;
        use GeneratesRecoveryCodes;
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

            $codes = $this->recoveryCodes($id);

            $codes = $this->decrypt($codes);

            return $this->response_factory->json($codes);

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

            return $this->response_factory->make(204);


        }


    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
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

            $codes = $this->recoveryCodes($request->user()->ID);

            $codes = $this->decrypt($codes);

            return $this->response_factory->json($codes);

        }

        public function update(Request $request)
        {

            $codes = $this->generateNewRecoveryCodes();
            $this->saveCodes($request->userId(), $codes);

            return $request->isExpectingJson()
                ? $this->response_factory->json([
                    'success' => true, 'message' => 'Recovery codes updated',
                ])
                : $this->response_factory->redirect()->back()->with('success.message', 'Recovery codes updated');

        }


    }
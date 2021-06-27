<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Illuminate\Support\Collection;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\GeneratesRecoveryCodes;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

    use function delete_user_meta;
    use function get_user_meta;
    use function update_user_meta;

    class TwoFactorAuthPreferenceController extends Controller
    {
        use GeneratesRecoveryCodes;

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

            $user = $request->userId();

            if (get_user_meta($user, 'two_factor_secret', true)) {

                $response = [
                    'success' => false,
                    'message' => 'Two-Factor Authentication is already enabled',
                ];

                return $request->isExpectingJson()
                    ? $this->response_factory->json($response, 403)
                    : $this->response_factory->back()->withErrors($response);

            }

            $secret = $this->provider->generateSecretKey();
            $backup_codes = $this->generateNewRecoveryCodes();

            update_user_meta($user, 'two_factor_secret', $secret);
            update_user_meta($user, 'two_factor_recovery_codes', $backup_codes);

            return $request->isExpectingJson()
                ? $this->response_factory->json([
                    'success' => true, 'message' => 'Two factory authentication enabled.',
                ])
                : $this->response_factory->back()
                                         ->with('2fa.status', 'enabled');

        }

        public function destroy(Request $request)
        {

            $user = $request->userId();

            if ( ! get_user_meta($user, 'two_factor_secret', true)) {

                $response = [
                    'success' => false,
                    'message' => 'Two-Factor Authentication is not enabled',
                ];

                return $request->isExpectingJson()
                    ? $this->response_factory->json($response, 403)
                    : $this->response_factory->back()->withErrors($response);

            }

            delete_user_meta($user, 'two_factor_secret');
            delete_user_meta($user, 'two_factor_recovery_codes');

            return $request->isExpectingJson()
                ? $this->response_factory->json([
                    'success' => true, 'message' => 'Two factory authentication disabled.',
                ])
                : $this->response_factory->back()
                                         ->with('2fa.status', 'disabled' );

        }

    }
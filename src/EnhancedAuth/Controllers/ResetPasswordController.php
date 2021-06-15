<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth\Controllers;

    use Carbon\Carbon;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\View\ViewFactory;
    use Respect\Validation\Validator as v;

    class ResetPasswordController
    {

        /**
         * @var UrlGenerator
         */
        private $url_generator;

        /**
         * @var ResponseFactory
         */
        private $response_factory;
        /**
         * @var string
         */
        private $app_key;
        /**
         * @var string|null
         */
        private $success_message;

        public function __construct(UrlGenerator $url_generator, ResponseFactory $response_factory, string $app_key, string $success_message = null)
        {

            $this->url_generator = $url_generator;
            $this->response_factory = $response_factory;
            $this->app_key = $app_key;
            $this->success_message = $success_message ?? 'You have successfully reset your password. You can now log-in with your new credentials';
        }

        public function create (Request $request, ViewFactory $view_factory, CsrfField $csrf_field)
        {

            $id = $request->input('id');

            $response = $this->response_factory->view('auth-parent', [
                'view' => 'password-reset',
                'view_factory' => $view_factory,
                'user_id' => $id,
                'signature' => $request->query('signature', ''),
                'post_to' => $this->url_generator->toRoute('reset.password.update'),
                'csrf_field' => $csrf_field->asHtml(),
            ])->withHeader('Referrer-Policy', 'strict-origin');

            return $response;


        }

        public function show (Request $request, ViewFactory $view_factory)
        {

            if ( ! $request->session()->get('_password_reset.success_message', false ) ) {

                return $this->response_factory->redirectToLogin();

            }

            return $view_factory->make('auth-parent.php')->with([
                'view' => 'password-reset-success',
                'view_factory' => $view_factory
            ]);


        }

        public function update ( Request $request, MagicLink $magic_link) : RedirectResponse
        {

            $reset = $request->session()->get('_password_reset', [] );

            if ( ! isset($reset['token']) || ! isset($reset['expires'] )) {

                return $this->redirectBackFailure($request);

            }

            $is_authorized = $this->authorizeReset($request, $reset);

            if ( ! $is_authorized ) {

                return $this->redirectBackFailure($request);

            }

            $user = $this->getUser($request);

            if ( ! $user instanceof \WP_User ) {

                return $this->redirectBackFailure($request);

            }

            $validated = $request->validate([
                'password' => v::noWhitespace()->length(12, 64),
                '*password_confirmation' => [v::sameAs('password'), 'The provided passwords do not match']
            ]);

            reset_password($user, $validated['password']);

            $magic_link->invalidate($request->session()->getPreviousUrl());

            return $this->response_factory->redirect()
                                          ->toRoute('reset.password.show')
                                          ->with([
                                                '_password_reset.success_message' => $this->success_message,
                                                ]);

        }

        private function authorizeReset(Request $request, $reset)
        {

            $token = $request->input('id', 'no-id')  . $request->input('signature', 'no-signature');

            $expected_token = $reset['token'];

            $valid = hash_equals($expected_token, hash_hmac('sha256', $token, $this->app_key));

            if ( $reset['expires'] < Carbon::now()->getTimestamp()) {
                $valid = false;
            }

            return $valid;


        }

        private function redirectBackFailure (Request $request) : RedirectResponse
        {

            return $this->response_factory->redirect()
                                          ->to($request->session()->getPreviousUrl(wp_login_url()))
                                          ->withErrors(['failure'=> 'We could not reset your password.']);


        }

        private function getUser(Request $request) {

            return get_user_by('id', (int) $request->input('id', 0));

        }

    }
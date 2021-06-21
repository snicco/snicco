<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Carbon\Carbon;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\View\ViewFactory;
    use Respect\Validation\Validator as v;

    class ResetPasswordController extends Controller
    {

        /**
         * @var string|null
         */
        protected $success_message;

        protected $rules = [];

        public function __construct(string $success_message = null)
        {

            $this->success_message = $success_message ?? 'You have successfully reset your password. You can now log-in with your new credentials';

        }

        public function create(Request $request, CsrfField $csrf_field)
        {

            $id = $request->input('id');

            $response = $this->response_factory->view('auth-parent', [
                'view' => 'password-reset',
                'view_factory' => $this->view_factory,
                'user_id' => $id,
                'signature' => $request->query('signature', ''),
                'post_to' => $request->fullPath(),
                'csrf_field' => $csrf_field->asHtml(),
            ])->withHeader('Referrer-Policy', 'strict-origin');

            return $response;


        }

        public function update(Request $request) : RedirectResponse
        {

            $user = $this->getUser($request);

            if ( ! $user instanceof \WP_User) {

                return $this->redirectBackFailure($request);

            }

            $rules = $this->rules !== []
                ? $this->rules
                : [
                    'password' => v::noWhitespace()->length(12, 64),
                    '*password_confirmation' => [
                        v::sameAs('password'), 'The provided passwords do not match',
                    ]
                ];

            $validated = $request->validate($rules);

            reset_password($user, $validated['password']);

            return $this->response_factory->redirect()
                                          ->refresh()
                                          ->with('_password_reset.success', true);

        }

        private function redirectBackFailure(Request $request) : RedirectResponse
        {

            return $this->response_factory->redirect()
                                          ->to($request->session()->getPreviousUrl(wp_login_url()))
                                          ->withErrors(['failure' => 'We could not reset your password.']);


        }

        private function getUser(Request $request)
        {

            $user = get_user_by('id', (int) $request->input('id', 0));

            return $user->ID === 0 ? null : $user;


        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Carbon\Carbon;
    use Closure;
    use Tests\unit\View\MethodField;
    use WP_User;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Validation\Exceptions\ValidationException;
    use WPEmerge\View\ViewFactory;
    use Respect\Validation\Validator as v;
    use ZxcvbnPhp\Zxcvbn;

    class ResetPasswordController extends Controller
    {

        /**
         * @var string|null
         */
        protected $success_message;

        protected $rules = [];

        protected $min_strength = 3;

        protected $min_length = 12;

        protected $max_length = 64;

        public function __construct(string $success_message = null)
        {

            $this->success_message = $success_message ?? 'You have successfully reset your password. You can now log-in with your new credentials';

        }

        public function create(Request $request, MethodField $method_field)
        {
            return $this->response_factory->view('auth-layout', [
                'view' => 'auth-password-reset',
                'post_to' => $request->fullPath(),
                'method_field' => $method_field->html('PUT')
            ])->withHeader('Referrer-Policy', 'strict-origin');

        }

        public function update(Request $request) : RedirectResponse
        {

            $user = $this->getUser($request);

            if ( ! $user instanceof WP_User ) {

                return $this->redirectBackFailure();

            }

            $rules = array_merge([
                'password' => v::noWhitespace()->length($this->min_length, $this->max_length),
                '*password_confirmation' => [
                    v::sameAs('password'), 'The provided passwords do not match',
                ],
            ], $this->rules);

            $validated = $request->validate($rules);

            $this->checkPasswordStrength($validated, $user);

            reset_password($user, $validated['password']);

            return $this->response_factory->redirect()
                                          ->refresh()
                                          ->with('_password_reset.success', true);

        }

        private function redirectBackFailure() : RedirectResponse
        {

            return $this->response_factory->redirect()
                                          ->refresh()
                                          ->withErrors(['failure' => 'We could not reset your password.']);

        }

        private function getUser(Request $request) : ?WP_User
        {

            $user = get_user_by('id', (int) $request->query('id', 0));

            return ( ! $user || $user->ID === 0) ? null : $user;


        }

        private function checkPasswordStrength(array $validated, WP_User $user)
        {

            $user_data = [
                $user->user_login,
                $user->user_email,
            ];

            $password_evaluator = new Zxcvbn();
            $result = $password_evaluator->passwordStrength($validated['password'], $user_data);

            if ($result['score'] < $this->min_strength) {


                $messages = $this->provideMessages($result);

                throw ValidationException::withMessages($messages);

            }

        }

        protected function provideMessages(array $result) : array
        {

            $messages = [
                'password' => [
                    'Your password is too weak and can be easily guessed by a computer.',
                ],
            ];

            if (isset($result['feedback']['warning'])) {

                $messages['reason'][] = trim($result['feedback']['warning'], '.') . '.';

            }

            foreach ($result['feedback']['suggestions'] ?? [] as $suggestion) {
                $messages['suggestions'][] = $suggestion;
            }

            return $messages;

        }

    }
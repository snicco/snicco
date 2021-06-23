<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WP_User;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Auth\Mail\ResetPasswordMail;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Session\CsrfField;
    use Respect\Validation\Validator as v;

    class ForgotPasswordController extends Controller
    {

        /**
         * @var int
         */
        protected $expiration;

        public function __construct( int $expiration = 3000 )
        {
            $this->expiration = $expiration;
        }

        public function create(CsrfField $csrf) : ViewInterface
        {

            return $this->view_factory->make('auth-parent')->with([
                'view' => 'auth-forgot-password',
                'view_factory' => $this->view_factory,
                'csrf_field' => $csrf->asHtml(),
                'post' => $this->url->toRoute('auth.forgot.password'),
            ]);

        }

        public function store(Request $request, MailBuilder $mail) : RedirectResponse
        {

            $login = $request->input('login');

            $user = $this->getUser($login);

            if ($user instanceof WP_User) {

                $magic_link = $this->generateSignedUrl($user);

                $mail->to($user->user_email)
                     ->send(new ResetPasswordMail($user, $magic_link, $this->expiration));

            }

            return $this->response_factory->redirect()
                                    ->toRoute('auth.forgot.password')
                                    ->with('_password_reset_processed', true);

        }

        private function getUser($login) {

            $is_email = v::email()->validate($login);

            return $is_email
                ? get_user_by('email', trim(wp_unslash($login)))
                : get_user_by('login', trim(wp_unslash($login)));

        }

        private function generateSignedUrl(WP_User $user ) : string
        {

            return $this->url->signedRoute(
                'auth.reset.password',
                ['query' => ['id' => $user->ID ] ],
                $this->expiration,
                true
            );

        }

    }
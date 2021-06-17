<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Carbon\Carbon;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Auth\Mail\ResetPasswordMail;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\View\ViewFactory;
    use Respect\Validation\Validator as v;

    class ForgotPasswordController
    {

        /**
         * @var string|null
         */
        private $success_message;

        /**
         * @var UrlGenerator
         */
        private $url;

        /**
         * @var int
         */
        private $expiration;

        /**
         * @var string
         */
        private $app_key;

        public function __construct(UrlGenerator $url, string $app_key, ?string $success_message = null, int $expiration = 3000 )
        {

            $this->success_message = $success_message ?? 'We sent an email with instructions to the associated account if it exists.';
            $this->url = $url;
            $this->expiration = $expiration;
            $this->app_key = $app_key;
        }

        public function create(ViewFactory $view_factory, CsrfField $csrf) : ViewInterface
        {

            return $view_factory->make('auth-parent')->with([
                'view' => 'auth-forgot-password',
                'view_factory' => $view_factory,
                'csrf_field' => $csrf->asHtml(),
                'post' => $this->url->toRoute('auth.forgot.password'),
            ]);

        }

        public function store(Request $request, MailBuilder $mail, ResponseFactory $response_factory) : RedirectResponse
        {

            $login = $request->input('login');

            $user = $this->getUser($login);

            if ($user instanceof \WP_User) {

                $magic_link = $this->generateSignedUrl($user);
                parse_str(parse_url($magic_link)['query'], $query);

                $mail->to($user->user_email)
                     ->send(new ResetPasswordMail($user, $magic_link, $this->expiration));

                $token = $user->ID.$query['signature'];
                $token = hash_hmac('sha256', $token, $this->app_key);

                $request->session()->put([
                    '_password_reset' => [
                        'token' => $token,
                        'expires' => Carbon::now()->addSeconds($this->expiration)->getTimestamp()
                    ]
                ]);

            }

            return $response_factory->redirect()
                                    ->toRoute('auth.forgot.password', 302)
                                    ->with('_password_reset_message', $this->success_message);

        }

        private function getUser($login) {

            $is_email = v::email()->validate($login);

            return $is_email
                ? get_user_by('email', trim(wp_unslash($login)))
                : get_user_by('login', trim(wp_unslash($login)));

        }

        private function generateSignedUrl(\WP_User $user ) : string
        {

            return $this->url->signedRoute(
                'auth.reset.password',
                ['query' => ['id' => $user->ID ] ],
                $this->expiration,
                true
            );

        }

    }
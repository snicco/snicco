<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WP_User;
    use WPEmerge\Auth\Mail\MagicLinkLoginMail;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Mail\MailBuilder;

    class LoginMagicLinkController extends Controller
    {

        use ResolvesUser;

        public function store(Request $request, MailBuilder $mail_builder) : RedirectResponse
        {

            $user = $this->getUserByLogin($request->input('login', ''));

            if ( ! $user instanceof WP_User) {

                return $this->redirectBack();

            }

            $magic_link = $this->createMagicLink($user, $expiration = 300);

            $mail_builder->to($user)
                         ->send(new MagicLinkLoginMail($user, $magic_link, $expiration));

            return $this->redirectBack();


        }

        private function redirectBack() : RedirectResponse
        {

            return $this->response_factory->redirect()
                                          ->toLogin()
                                          ->with('login.link.success', true);
        }

        protected function createMagicLink($user, $expiration = 300) : string
        {

            $args = [
                'query' => ['user_id' => $user->ID],
            ];

            return $this->url->signedRoute('auth.login.magic-link', $args, $expiration, true);

        }

    }
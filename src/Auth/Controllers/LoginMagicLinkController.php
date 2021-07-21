<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Controllers;

    use WP_User;
    use Snicco\Auth\Mail\MagicLinkLoginMail;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Http\Controller;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Responses\RedirectResponse;
    use Snicco\Mail\MailBuilder;

    class LoginMagicLinkController extends Controller
    {

        use ResolvesUser;

        public function store(Request $request, MailBuilder $mail_builder) : RedirectResponse
        {

            $user = $this->getUserByLogin($request->input('login', ''));

            if ( ! $user instanceof WP_User ) {

                return $this->redirectBack();

            }

            $magic_link = $this->createMagicLink($user, $expiration = 300);

            $mail_builder->to($user)
                         ->send(new MagicLinkLoginMail($user, $magic_link, $expiration));

            return $this->redirectBack();


        }

        // Always redirect back with a generic message.
        private function redirectBack() : RedirectResponse
        {

            return $this->response_factory->back($this->url->toRoute('auth.login'))
                                          ->with('login.link.processed', true);
        }

        protected function createMagicLink($user, $expiration = 300) : string
        {

            $args = [
                'query' => ['user_id' => $user->ID],
            ];

            return $this->url->signedRoute('auth.login.magic-link', $args, $expiration, true);

        }

    }
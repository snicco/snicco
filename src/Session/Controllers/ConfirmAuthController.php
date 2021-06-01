<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use WP_User;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    use function get_user_by;

    class ConfirmAuthController
    {

        /**
         * @var ViewFactory
         */
        private $view;
        /**
         * @var UrlGenerator
         */
        private $url_generator;
        /**
         * @var ResponseFactory
         */
        private $response_factory;

        protected $link_lifetime_in_sec = 300;

        protected $attempts = 3;

        public function __construct(ViewFactory $view, UrlGenerator $url_generator, ResponseFactory $response_factory)
        {

            $this->view = $view;
            $this->url_generator = $url_generator;
            $this->response_factory = $response_factory;

        }

        public function show(CsrfField $csrf_field, SessionStore $session)
        {

            $post_url = $this->url_generator->toRoute('auth.confirm.send', [], true, false);

            $html = $csrf_field->asHtml();

            $attempts = $session->increment('auth.confirm.attempts');

            $session->keep('auth.confirm.intended_url');

            if ( $attempts > $this->attempts) {

                $session->invalidate();
                WP::logout();

                return $this->response_factory->redirect(429)->to(WP::loginUrl());

            }


            return $this->view->make('auth-confirm')->with([
                'post_url' => $post_url, 'csrf_field' => $html,
            ]);

        }

        public function send(Request $request, SessionStore $session)
        {

            $email = Arr::get($request->getParsedBody(), 'email', '');

            $user = get_user_by('email', $email);


            if ( ! $user instanceof WP_User) {

                $session->keep('auth.confirm.intended_url');

                $bag = new MessageBag();
                $bag->add('email', 'The email you entered is not linked with any account.');
                $errors = $session->get('errors', new ViewErrorBag);
                $session->flash(
                    'errors', $errors->put('default', $bag)
                );

                $session->flashInput(['email' => $email]);

                return $this->response_factory->redirect(404)->to($request->getFullUrl());

            }

            $session->flashInput(['email' => $email]);
            $session->flash('auth.confirm.success', 'Success: A confirmation email was send to: '.$email);
            $session->flash('auth.confirm.lifetime', $this->link_lifetime_in_sec);
            $session->forget('auth.confirm.attempts');

            WP::mail($email, $this->subject($user), $this->message($user, $session) );

            return $this->response_factory->redirect(200)->to($request->getFullUrl());


        }

        protected function subject(WP_User $user) : string
        {

            return 'Your Email Confirmation link';

        }

        private function generateSignedUrl(WP_User $user, SessionStore $session) : string
        {

            $arguments = [
                'user_id' => $user->ID,
                'query'=> [
                    'intended'=> $session->get('auth.confirm.intended_url')
                ]
            ];

            return $this->url_generator->signedRoute('auth.confirm.magic-login', $arguments);

        }

        protected function message(WP_User $user, SessionStore $session_store) : string
        {

            $signed_url = $this->generateSignedUrl($user, $session_store);

            return $this->view->render(
                'auth-confirm-email',
                [
                    'user' => $user,
                    'magic_link' => $signed_url,
                    'lifetime' => $this->link_lifetime_in_sec,
                ]
            );


        }

    }
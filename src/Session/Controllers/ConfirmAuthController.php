<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Carbon\Carbon;
    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use WP_User;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
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

        protected $resends = 3;

        protected $timeout_in_minutes = 15;

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

            if ( $attempts > $this->attempts ) {

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

            if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {

                return $this->redirectBack($session, $request, $email);

            }

            if ($this->toManyRequests($session)) {

                return $this->response_factory
                    ->redirect(429)
                    ->to($request->getFullUrl());

            }

            $user = get_user_by('email', $email);

            if ( ! $user instanceof WP_User) {

                return $this->redirectBack($session, $request, $email);

            }

            $session->flashInput(['email' => $email]);
            $session->flash('auth.confirm.success', 'Success: A confirmation email was send to: '.$email);
            $session->flash('auth.confirm.lifetime', $this->link_lifetime_in_sec);

            $success = WP::mail($email, $this->subject($user), $this->message($user, $session));

            if ($success) {
                $session->increment('auth.confirm.email.count');
                $session->forget('auth.confirm.attempts');
            }

            return $this->response_factory->redirect(200)->to($request->getFullUrl());


        }

        protected function subject(WP_User $user) : string
        {

            return 'Your Email Confirmation link';

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

        private function generateSignedUrl(WP_User $user, SessionStore $session) : string
        {

            $arguments = [
                'user_id' => $user->ID,
                'query' => [
                    'intended' => $session->get('auth.confirm.intended_url'),
                ],
            ];

            return $this->url_generator->signedRoute('auth.confirm.magic-login', $arguments);

        }

        private function redirectBack(SessionStore $session, Request $request, $email) : RedirectResponse
        {

            $session->keep('auth.confirm.intended_url');

            $bag = new MessageBag();
            $bag->add('email', 'Error: The email you entered is not linked with any account.');
            $errors = $session->get('errors', new ViewErrorBag);
            $session->flash(
                'errors', $errors->put('default', $bag)
            );

            $session->flashInput(['email' => $email]);

            return $this->response_factory->redirect(404)->to($request->getFullUrl());

        }

        private function toManyRequests(SessionStore $session) : bool
        {

            $exceeded_attempts = $session->get('auth.confirm.email.count', 0) >= $this->resends;

            if ( ! $exceeded_attempts ) {
                return false;
            }

            if ( ! $session->has('auth.confirm.email.next') ) {

                $session->put(
                    'auth.confirm.email.next',
                    Carbon::now()->addMinutes($this->timeout_in_minutes)->getTimestamp()
                );

            }

            if ( $this->canReceiveNextEmail($session) ) {

                $session->forget('auth.confirm.email');

                return false;
            }

            return true;

        }

        private function canReceiveNextEmail(SessionStore $session) : bool
        {

            if ($session->get('auth.confirm.email.next', 0) < Carbon::now()->getTimestamp()) {

                return true;

            }

            return false;

        }

    }
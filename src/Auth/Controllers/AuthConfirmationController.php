<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use Carbon\Carbon;
    use WP_User;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Auth\Mail\ConfirmAuthMail;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    use function get_user_by;

    class AuthConfirmationController
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

        protected $transient_key = 'user_emails_in_jail';

        /**
         * @var Session
         */
        private $session;

        public function __construct(ViewFactory $view, UrlGenerator $url_generator, ResponseFactory $response_factory, Session $session)
        {

            $this->view = $view;
            $this->url_generator = $url_generator;
            $this->response_factory = $response_factory;
            $this->session = $session;

        }

        public function create(CsrfField $csrf_field) : ViewInterface
        {

            $post_url = $this->url_generator->toRoute('auth.confirm.send', [], true, false);

            return $this->view->make('auth-confirm')
                              ->with(
                                  [
                                      'post_url' => $post_url,
                                      'csrf_field' => $csrf_field->asHtml(),
                                      'jail' => $this->isUserInJail() ? $this->getJailTime() : false,
                                      'last_recipient' => $this->lastRecipient(),
                                      'view' => $this->view,
                                  ]
                              );

        }

        public function send(Request $request, MailBuilder $mail) : Response
        {

            if ( ! $this->hasLeftAttemptsToInputCorrectEmail() ) {

                $this->session->invalidate();
                WP::logout();

                return $this->response_factory->redirectToLogin(true, $request->path());

            }

            if ( ! $this->canRequestAnotherEmail()) {

                return $this->response_factory->redirect()->refresh();

            }

            if ( ! ( $email = $this->hasValidEmailInput($request) ) ) {

                return $this->redirectBack($email);

            }

            if ( ! ( $user = $this->userWithEmailExists($email) ) ) {

                $this->session->increment('auth.confirm.attempts');

                return $this->redirectBack($email);

            }

            $success = $mail->to($email)
                            ->send(new ConfirmAuthMail($user, $this->link_lifetime_in_sec));


            $redirect = $this->response_factory->redirect()->refresh(303);

            if ($success) {

                $this->session->put('auth.confirm.lifetime', $this->link_lifetime_in_sec);
                $this->session->put('auth.confirm.email.last_recipient', $email);
                $this->session->increment('auth.confirm.email.count');
                $this->session->forget('auth.confirm.attempts');
                $redirect->with('auth.confirm.success', 'Success: A confirmation email was send to: '.$email);

            }
            else {

                $redirect->with('auth.confirm.email_sending_failed', true);

            }

            return $redirect;


        }



        private function redirectBack(?string $email) : RedirectResponse
        {

            return $this->response_factory->redirect()
                                          ->refresh()
                                          ->withInput(['email' => $email])
                                          ->withErrors(['email' => 'Error: The email you entered is not linked with any account.']);

        }

        private function canRequestAnotherEmail() : bool
        {

            $exceeded_attempts = $this->session->get('auth.confirm.email.count', 0) >= $this->resends;

            if ( ! $exceeded_attempts) {

                return true;

            }

            if ( ! $this->session->has('auth.confirm.email.jail')) {

                $this->putUserInJail();

            }

            if ( ! $this->isUserInJail()) {

                $this->session->forget('auth.confirm.email');

                return true;

            }

            return false;

        }

        private function hasLeftAttemptsToInputCorrectEmail() : bool
        {

            $attempts = $this->session->get('auth.confirm.attempts', 0);

            return $attempts < $this->attempts;
        }

        private function hasValidEmailInput(Request $request)
        {

            $email = Arr::get($request->getParsedBody(), 'email', '');

            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;

            return $email;

        }

        private function userWithEmailExists(string $email)
        {

            return get_user_by('email', $email);

        }

        private function isUserInJail() : bool
        {

            if ($this->session->get('auth.confirm.email.jail', 0) >= Carbon::now()
                                                                           ->getTimestamp()) {

                return true;

            }

            $in_jail = get_transient($this->transient_key.WP::userId());

            if ($in_jail && $in_jail >= Carbon::now()->getTimestamp()) {

                return true;

            }

            return false;

        }

        private function putUserInJail()
        {

            $this->session->put('auth.confirm.email.jail',
                Carbon::now()->addMinutes($this->timeout_in_minutes)->getTimestamp()
            );

            $expiration = Carbon::now()->addMinutes($this->timeout_in_minutes)->getTimestamp();

            /** @todo replace transient api with dedicated db-table when we have more session feature in the future that might need a jail. */
            set_transient($this->transient_key.WP::userId(), $expiration, $expiration);

        }

        private function getJailTime() : int
        {

            $in_jail_until = $this->session->get('auth.confirm.email.jail');

            if (is_int($in_jail_until)) {

                return $in_jail_until;

            }

            $in_jail_db = get_transient($this->transient_key.WP::userId());

            if ($in_jail_db !== false) {

                return (int) $in_jail_db;

            }

            return Carbon::now()->addMinutes($this->timeout_in_minutes)->getTimestamp();


        }

        private function lastRecipient()
        {

            return $this->session->get('auth.confirm.email.last_recipient', '');
        }

    }
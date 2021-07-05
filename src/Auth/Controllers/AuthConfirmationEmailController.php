<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Controllers;

    use Illuminate\Support\InteractsWithTime;
    use WP_User;
    use WPMvc\Auth\Mail\ConfirmAuthMail;
    use WPMvc\Http\Controller;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Mail\MailBuilder;
    use WPMvc\Session\Session;

    class AuthConfirmationEmailController extends Controller
    {

        use InteractsWithTime;

        /**
         * @var int
         */
        private $cool_of_period;
        /**
         * @var int
         */
        private $link_lifetime_in_seconds;
        /**
         * @var MailBuilder
         */
        private $mail_builder;

        public function __construct(MailBuilder $mail_builder, int $cool_of_period = 15, $link_lifetime_in_seconds = 300)
        {

            $this->cool_of_period = $cool_of_period;
            $this->link_lifetime_in_seconds = $link_lifetime_in_seconds;
            $this->mail_builder = $mail_builder;
        }

        protected function errorMessage() : string
        {

            return "You have requested too many emails. You can request your next email in $this->cool_of_period seconds.";
        }

        public function store(Request $request)
        {

            $user = $request->user();
            $session = $request->session();

            if ( ! $this->canRequestAnotherEmail($session)) {

                return $request->isExpectingJson()
                    ? $this->response_factory->json(['message' => $this->errorMessage()], 429)
                    : $this->response_factory->back()
                                             ->withErrors([
                                                 'auth.confirm.email.message' => $this->errorMessage(),
                                             ]);

            }

            $this->sendConfirmationMailTo($user, $session);

            return $request->isExpectingJson()
                ? $this->response_factory->make(204)
                : $this->response_factory->redirect()->back();

        }

        private function canRequestAnotherEmail(Session $session) : bool
        {


            $last = $session->get('auth.confirm.email.next', 0);

            if ($this->currentTime() < $last) {

                return false;

            }

            return true ;


        }

        private function sendConfirmationMailTo(WP_User $user, Session $session)
        {

            $session->flash('auth.confirm.email.sent', true);
            $session->put('auth.confirm.email.next', $this->availableAt($this->cool_of_period));
            $session->put('auth.confirm.email.cool_off', $this->cool_of_period);

            $this->mail_builder->to($user)
                               ->send(new ConfirmAuthMail(
                                       $user,
                                       $this->link_lifetime_in_seconds
                                   )
                               );
        }

    }
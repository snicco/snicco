<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Confirmation;

    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Auth\Mail\ConfirmAuthMail;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Session\Session;

    class EmailAuthConfirmation implements AuthConfirmation
    {

        use InteractsWithTime;

        /**
         * @var MagicLink
         */
        private $magic_link;
        /**
         * @var MailBuilder
         */
        private $mail_builder;

        protected $link_lifetime = 300;

        protected $cool_off_period = 15;

        /**
         * @var ResponseFactory
         */
        private $response;

        public function __construct(MagicLink $magic_link, MailBuilder $mail_builder, ResponseFactory $response)
        {
            $this->magic_link = $magic_link;
            $this->mail_builder = $mail_builder;
            $this->response = $response;
        }

        public function prepare(Request $request) : AuthConfirmation
        {

            $session = $request->session();

            if ( ! $this->canRequestAnotherEmail($session) ) {

                $session->put('auth.confirm.cool_off_period', $this->cool_off_period );
                return $this;

            }

            $session->put('auth.confirm.email', $this->currentTime());
            $session->put('auth.confirm.email_sent', true );

            $this->mail_builder->to($user = WP::currentUser())
                               ->send(new ConfirmAuthMail($user, $this->link_lifetime));

            return $this;

        }

        public function confirm(Request $request)
        {

            $valid = $this->magic_link->hasValidSignature($request, true );

            if ( ! $valid ) {

                $session = $request->session();

                if ( $this->canRequestAnotherEmail($session ) ) {

                    $session->put('auth.confirm.can_request_another_email', true );

                } else {

                    $session->put('auth.confirm.can_request_another_email', false );
                    $session->put('auth.confirm.cool_off_period', $this->cool_off_period );

                }

                return ['message' => 'Authentication failed.'];

            }

            $this->magic_link->invalidate($request->fullUrl());
            return true;

        }

        public function viewResponse(Request $request) : Response
        {

            return $this->response->view('auth-parent', [
                'view' => 'auth-confirm-via-email'
            ]);

        }

        public function canRequestAnotherEmail(Session $session) : bool
        {


            $last = $session->get('auth.confirm.email');

            if ( $last && $this->currentTime() - $last < $this->cool_off_period  ) {

                return false;

            }

            return true;

        }

    }
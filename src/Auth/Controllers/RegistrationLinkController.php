<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Controllers;

    use BetterWP\Auth\Mail\ConfirmRegistrationEmail;
    use BetterWP\Auth\Contracts\RegistrationViewResponse;
    use BetterWP\Contracts\ResponsableInterface;
    use BetterWP\Http\Controller;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Responses\RedirectResponse;
    use BetterWP\Mail\MailBuilder;

    class RegistrationLinkController extends Controller
    {

        /**
         * @var int
         */
        private $lifetime_in_seconds;

        public function __construct($lifetime_in_seconds = 600 )
        {
            $this->lifetime_in_seconds = $lifetime_in_seconds;
        }

        public function create(Request $request, RegistrationViewResponse $response) : ResponsableInterface
        {

            return $response->setRequest($request);

        }

        public function store(Request $request, MailBuilder $mail_builder) : RedirectResponse
        {

            $email = $request->input('email', '');

            $valid = filter_var($email, FILTER_VALIDATE_EMAIL);

            if ( ! $valid ) {

                return $this->response_factory->back()
                                              ->withErrors(['email' => 'That email address does not seem to be valid.']);

            }

            $request->session()->put('registration.email', $email);
            $link = $this->url->signedRoute('auth.accounts.create', [], $this->lifetime_in_seconds, true);

            $mail_builder->to($email)->send(new ConfirmRegistrationEmail($link) );

            return $this->response_factory->back()
                                          ->with('registration.link.success', true);

        }

    }
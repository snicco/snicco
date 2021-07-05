<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Controllers;

    use WPMvc\Auth\Mail\ConfirmRegistrationEmail;
    use WPMvc\Auth\Contracts\RegistrationViewResponse;
    use WPMvc\Contracts\ResponsableInterface;
    use WPMvc\Http\Controller;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Responses\RedirectResponse;
    use WPMvc\Mail\MailBuilder;

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
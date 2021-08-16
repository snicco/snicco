<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Controllers;

    use Snicco\Http\Controller;
    use Snicco\Mail\MailBuilder;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Responses\RedirectResponse;
    use Snicco\Contracts\ResponseableInterface;
    use Snicco\Auth\Mail\ConfirmRegistrationEmail;
    use Snicco\Auth\Contracts\RegistrationViewResponse;

    class RegistrationLinkController extends Controller
    {
    
        private int $lifetime_in_seconds;
    
        public function __construct($lifetime_in_seconds = 600)
        {
            $this->lifetime_in_seconds = $lifetime_in_seconds;
        }
    
        public function create(Request $request, RegistrationViewResponse $response) :ResponseableInterface
        {
        
            return $response->forRequest($request);
        
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
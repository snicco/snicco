<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Mail\ConfirmRegistrationEmail;
    use WPEmerge\Auth\Contracts\RegistrationViewResponse;
    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Mail\MailBuilder;

    class RegistrationLinkController extends Controller
    {

        public function create(Request $request, RegistrationViewResponse $response) : ResponsableInterface
        {

            return $response->setRequest($request);

        }

        public function store(Request $request, MailBuilder $mail_builder) : RedirectResponse
        {

            $email = $request->input('email', '');

            $valid = filter_var($email, FILTER_VALIDATE_EMAIL);

            if ( ! $valid) {

                return $this->response_factory->back()
                                              ->withErrors(['email' => 'That email address does not seem to be valid.']);

            }

            $request->session()->put('registration.email', $email);
            $link = $this->url->signedRoute('auth.register.confirm', [], 300, true);

            $mail_builder->to($email)->send(new ConfirmRegistrationEmail($link));

            return $this->response_factory->back()
                                          ->with('registration.link.success', true);

        }



    }
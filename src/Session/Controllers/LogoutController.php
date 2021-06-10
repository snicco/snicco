<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\Exceptions\InvalidSignatureException;

    class LogoutController
    {

        public function __invoke(Request $request, string $user_id, Redirector $redirector) : RedirectResponse
        {


            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            $request->getSession()->invalidate();

            WP::logout();

            $redirect_to = $request->getQueryString('redirect_to', WP::homeUrl());

            return $redirector->to($redirect_to)
                              ->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                              ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

        }

    }
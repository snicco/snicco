<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;

    class LogoutController
    {

        public function __invoke(Request $request, string $user_id, Redirector $redirector, MagicLink $magic_link) : RedirectResponse
        {

            if ((int) $user_id !== WP::userId()) {

                throw new InvalidSignatureException();

            }

            $request->session()->invalidate();

            WP::logout();

            $magic_link->invalidate($request->fullUrl());

            $redirect_to = $request->query('redirect_to', WP::homeUrl());

            return $redirector->to($redirect_to)
                              ->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                              ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

        }

    }
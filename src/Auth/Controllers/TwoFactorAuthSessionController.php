<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

    class TwoFactorAuthSessionController extends Controller
    {

        public function create(Request $request)
        {

            if ( ! $request->session()->challengedUser() ) {

                return $this->response_factory->redirectToLogin();

            }

            return $this->view_factory->make('auth-parent')->with([
                'view' => 'auth-two-factor-challenge',
                'post_to' => $this->url->toLogin(),
            ]);

        }

    }
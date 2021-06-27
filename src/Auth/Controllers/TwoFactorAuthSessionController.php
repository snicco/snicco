<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

    class TwoFactorAuthSessionController extends Controller
    {

        use ResolveTwoFactorSecrets;
        use ResolvesUser;

        public function create(Request $request)
        {

            $challenged_user = $request->session()->challengedUser();

            if ( ! $challenged_user || ! $this->userHasTwoFactorEnabled($this->getUserById($challenged_user))) {

                return $this->response_factory->redirectToLogin();

            }

            return $this->view_factory->make('auth-layout')->with([
                'view' => 'auth-two-factor-challenge',
                'post_to' => $this->url->toLogin(),
            ]);

        }

    }
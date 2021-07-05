<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Traits\InteractsWithTwoFactorSecrets;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

    class TwoFactorAuthSessionController extends Controller
    {

        use InteractsWithTwoFactorSecrets;
        use ResolvesUser;

        public function create(Request $request)
        {

            $challenged_user = $request->session()->challengedUser();

            if ( ! $challenged_user || ! $this->userHasTwoFactorEnabled($this->getUserById($challenged_user))) {

                return $this->response_factory->redirect()->toRoute('auth.login');

            }

            return $this->view_factory->make('auth-layout')->with([
                'view' => 'auth-two-factor-challenge',
                'post_to' => $this->url->toLogin(),
            ]);

        }

    }
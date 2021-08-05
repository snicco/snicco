<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Authenticators;

    use Snicco\Auth\Contracts\Authenticator;
    use Snicco\Auth\Exceptions\FailedAuthenticationException;
    use Snicco\Auth\Traits\ResolvesUser;
    use Snicco\Http\Psr7\Request;
    use WP_Error;
    use WP_User;

    class PasswordAuthenticator extends Authenticator
    {

        use ResolvesUser;

        protected string $failure_message = 'Your password or username is not correct.';

        public function attempt(Request $request, $next)
        {

            if ( ! $request->filled('pwd') || ! $request->filled('log')) {

                throw new FailedAuthenticationException($this->failure_message, $request, $request->only([
                    'pwd', 'log',
                ]));

            }

            $password = $request->input('pwd');
            $username = $request->input('log');
            $remember = $request->boolean('remember_me');

            $user = $this->getUserByLogin($username);

            if ( ! $user instanceof WP_User) {

                $this->fail($username, $remember, new WP_Error(404, 'Unkown username or email.'), $request);

            }

            $valid_pw = wp_check_password($password, $user->user_pass, $user->ID);

            if ( ! $valid_pw) {

                $this->fail($username, $remember, new WP_Error(400, 'incorrect password provided for login.'), $request);

            }

            return $this->login($user, $remember);

        }

        /**
         * @throws FailedAuthenticationException
         */
        private function fail($username, $remember, WP_Error $error, Request $request)
        {

            // compatibility
            do_action('wp_login_failed', $username, $error);

            throw new FailedAuthenticationException($this->failure_message, $request, [
                'log' => $username,
                'remember_me' => $remember,
            ],
            );

        }

    }
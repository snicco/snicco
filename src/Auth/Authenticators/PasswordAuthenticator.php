<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Authenticators;

    use WP_User;
    use Snicco\Auth\Contracts\Authenticator;
    use Snicco\Auth\Exceptions\FailedAuthenticationException;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;

    class PasswordAuthenticator extends Authenticator
    {

        protected $failure_message = 'Your password or username is not correct.';

        public function attempt(Request $request, $next)
        {

            if ( ! $request->filled('pwd') || ! $request->filled('log') ) {

                throw new FailedAuthenticationException($this->failure_message, $request, $request->only([
                    'pwd', 'log',
                ]));

            }

            $password = $request->input('pwd');
            $username = $request->input('log');
            $remember = $request->boolean('remember_me');

            $user = wp_authenticate_username_password(null, $username, $password);

            if ( ! $user instanceof WP_User) {

                $this->fail($username, $remember, $user, $request);

            }

            return $this->login($user, $remember);

        }

        private function fail($username, $remember, \WP_Error $error, Request $request)
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
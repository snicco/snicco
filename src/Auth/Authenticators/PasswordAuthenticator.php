<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WP_User;
    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class PasswordAuthenticator extends Authenticator
    {

        protected $failure_message = 'Your password or username is not correct.';

        public function attempt(Request $request, $next) : Response
        {

            if ( ! $request->has('pwd') || ! $request->has('log') ) {

                throw new FailedAuthenticationException($this->failure_message, $request->only(['pwd', 'log']));

            }

            $password =  $request->input('pwd');
            $username = $request->input('log');

            $user = wp_authenticate_username_password(null, $username, $password );

            if ( ! $user instanceof WP_User ) {

                do_action( 'wp_login_failed', $username, $user );

                throw new FailedAuthenticationException($this->failure_message,
                    [
                        'username' => $username,
                        'remember_me' => $request->input('remember_me', 'off')
                    ]
                );

            }

            return $this->loginResponse($user);
        }

    }
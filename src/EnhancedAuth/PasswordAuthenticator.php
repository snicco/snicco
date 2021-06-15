<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth;

    use WP_User;
    use WPEmerge\EnhancedAuth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Http\Psr7\Request;

    class PasswordAuthenticator implements Authenticator
    {

        /**
         * @var string
         */
        private $failure_message;

        public function __construct(string $failure_message = 'Your password or username is not correct.')
        {
            $this->failure_message = $failure_message;
        }

        public function authenticate(Request $request) : WP_User
        {

            if ( ! $request->has('pwd') || ! $request->has('log') ) {

                throw new FailedAuthenticationException($this->failure_message, []);

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

            return $user;

        }


        public function view() : string
        {
            return 'auth-login-password';
        }

    }
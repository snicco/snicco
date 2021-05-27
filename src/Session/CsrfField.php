<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
    use WPEmerge\Support\Arr;

    class CsrfField
    {

        /**
         * @var SessionStore
         */
        private $session;

        /**
         * @var Guard
         */
        private $guard;

        public function __construct(SessionStore $session, Guard $guard)
        {
            $this->session = $session;
            $this->guard = $guard;
        }

        public function create() : array
        {

            $name_key = $this->guard->getTokenNameKey();
            $token_key = $this->guard->getTokenValueKey();

            if ( ( $csrf = $this->session->get('csrf', [] ) ) !== [] ) {

               [ $name_key_value, $token_value ] = [Arr::firstKey($csrf), Arr::firstEl($csrf)];

            } else {
                [ $name_key_value, $token_value ] = $this->persistNewKeyPairInSession();
            }

            return [
                $name_key => $name_key_value,
                $token_key => $token_value
            ];

        }

        private function persistNewKeyPairInSession() : array
        {
            return array_values($this->guard->generateToken());
        }


    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;


    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Support\Arr;

    class AuthSessionValidator
    {

        use InteractsWithTime;

        /**
         * @var array
         */
        private $auth_config;

        public function __construct(array $auth_config)
        {
            $this->auth_config = $auth_config;
        }

        public function isIdle(array $session_payload) : bool
        {

            if ( Arr::get($this->auth_config, 'remember.enabled', false ) ) {

                return false;

            }

            $last_activity = $session_payload['_last_activity'] ?? 0;

            $idle_timeout = Arr::get($this->auth_config, 'timeouts.idle', 0);

            return $last_activity + $idle_timeout < $this->currentTime();

        }





    }
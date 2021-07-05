<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Events;

    use WP_User;
    use BetterWP\Events\Event;

    class SettingAuthCookie extends Event
    {

        private $cookie;

        public $user_id;
        public $expiration;

        /**
         * @var WP_User
         */
        public  $user;

        public $scheme;

        public function __construct($cookie, $user_id, $expiration, $scheme)
        {

            $this->cookie = $cookie;
            $this->user_id = $user_id;
            $this->user = get_user_by('id', $this->user_id);
            $this->expiration = $expiration;

            $this->scheme = $scheme;
        }

        public function default() :string {

            return $this->cookie;

        }

    }
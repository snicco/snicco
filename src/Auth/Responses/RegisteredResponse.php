<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Auth\Traits\UsesCurrentRequest;
    use WPEmerge\Contracts\ResponsableInterface;

    abstract class RegisteredResponse implements ResponsableInterface
    {

        use UsesCurrentRequest;

        /**
         * @var \WP_User
         */
        protected $user;

        public function setUser(\WP_User $user) {
            $this->user = $user;
            return $this;
        }
    }
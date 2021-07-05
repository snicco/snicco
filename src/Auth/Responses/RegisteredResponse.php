<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Responses;

    use BetterWP\Auth\Traits\UsesCurrentRequest;
    use BetterWP\Contracts\ResponsableInterface;

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
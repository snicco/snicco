<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Responses;

    use WPMvc\Auth\Traits\UsesCurrentRequest;
    use WPMvc\Contracts\ResponsableInterface;

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
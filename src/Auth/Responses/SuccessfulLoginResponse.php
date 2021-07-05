<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Responses;

    use Psr\Http\Message\ResponseInterface;
    use WP_User;
    use WPMvc\Http\Psr7\Response;

    class SuccessfulLoginResponse extends Response
    {

        /**
         * @var WP_User
         */
        private $user;

        private $remember;

        public function __construct(ResponseInterface $psr7_response, WP_User $user, bool $remember)
        {

            parent::__construct($psr7_response);
            $this->user = $user;
            $this->remember = $remember;

        }

        public function authenticatedUser() : WP_User
        {

            return $this->user;
        }

        public function rememberUser() : bool
        {

            return $this->remember;

        }

    }
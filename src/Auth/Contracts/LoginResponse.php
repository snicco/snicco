<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use WP_User;
    use Snicco\Contracts\ResponsableInterface;
    use Snicco\Http\Psr7\Request;

    abstract class LoginResponse implements ResponsableInterface
    {

        /**
         * @var Request
         */
        protected $request;

        /**
         * @var WP_User
         */
        protected $user;

        public function forRequest(Request $request) : LoginResponse
        {
            $this->request = $request;
            return $this;
        }

        public function forUser(WP_User $user) : LoginResponse
        {
            $this->user = $user;
            return $this;
        }

    }
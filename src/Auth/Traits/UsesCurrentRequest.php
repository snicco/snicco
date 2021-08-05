<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Traits;

    use Snicco\Http\Psr7\Request;
    use WP_User;

    trait UsesCurrentRequest
    {

        protected Request $request;
        protected WP_User $user;

        public function forRequest(Request $request) :self {
            $this->request = $request;
            return $this;
        }

        public function forUser (WP_User $user) :self  {
            $this->user = $user;
            return $this;
        }

    }
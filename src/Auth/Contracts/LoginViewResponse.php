<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Contracts\ResponsableInterface;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Arr;

    abstract class LoginViewResponse implements ResponsableInterface
    {
        /** @var Request */
        protected $request;

        /** @var array */
        protected $auth_config;

        public function withRequest(Request $request) : LoginViewResponse
        {
            $this->request = $request;
            return $this;
        }

        public function withAuthConfig(array $config) : LoginViewResponse
        {
            $this->auth_config = $config;
            return $this;
        }

        protected function allowRememberMe() : bool
        {

            return Arr::get($this->auth_config, 'features.remember_me', false);

        }

    }
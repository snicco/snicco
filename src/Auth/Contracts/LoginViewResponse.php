<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Contracts;

    use BetterWP\Contracts\ResponsableInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Support\Arr;

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

            return Arr::get($this->auth_config, 'remember.enabled', true);

        }

    }
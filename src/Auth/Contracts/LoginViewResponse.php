<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponsableInterface;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Arr;

    abstract class LoginViewResponse implements ResponsableInterface
    {

        use UsesCurrentRequest;

        protected Request $request;

        protected array $auth_config;

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
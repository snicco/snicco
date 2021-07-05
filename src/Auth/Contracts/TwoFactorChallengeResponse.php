<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Contracts;

    use WPMvc\Contracts\ResponsableInterface;
    use WPMvc\Http\Psr7\Request;

    abstract class TwoFactorChallengeResponse implements ResponsableInterface
    {
        /**
         * @var Request
         */
        protected $request;

        public function setRequest(Request $request) : TwoFactorChallengeResponse
        {
            $this->request = $request;
            return $this;
        }

    }
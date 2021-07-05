<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Contracts;

    use BetterWP\Contracts\ResponsableInterface;
    use BetterWP\Http\Psr7\Request;

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
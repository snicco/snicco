<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Contracts\ResponsableInterface;
    use Snicco\Http\Psr7\Request;

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
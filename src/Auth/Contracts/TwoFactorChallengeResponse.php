<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Contracts;

    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Http\Psr7\Request;

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
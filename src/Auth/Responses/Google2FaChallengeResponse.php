<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Responses;

    use Snicco\Auth\Contracts\TwoFactorChallengeResponse;
    use Snicco\Http\ResponseFactory;

    class Google2FaChallengeResponse extends TwoFactorChallengeResponse
    {

        private ResponseFactory $response_factory;

        public function __construct(ResponseFactory $response_factory)
        {
            $this->response_factory = $response_factory;
        }

        public function toResponsable()
        {

            return $this->response_factory->redirect()->toRoute('auth.2fa.challenge');


        }

    }
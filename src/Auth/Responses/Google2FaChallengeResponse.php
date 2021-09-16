<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Http\ResponseFactory;
use Snicco\Auth\Contracts\AbstractTwoFactorChallengeResponse;

class Google2FaChallengeResponse extends AbstractTwoFactorChallengeResponse
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
<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Component\Core\Shared\Encryptor;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Component\HttpRouting\Http\AbstractController;

class TwoFactorAuthSessionAbstractController extends AbstractController
{
    
    use InteractsWithTwoFactorSecrets;
    use ResolvesUser;
    
    public function __construct(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }
    
    public function create(Request $request, Abstract2FAChallengeView $view_response)
    {
        $challenged_user = $request->session()->challengedUser();
        
        if ( ! $challenged_user
             || ! $this->userHasTwoFactorEnabled(
                $this->getUserById($challenged_user)
            )) {
            return $this->response_factory->redirect()->toRoute('auth.login');
        }
        
        return $view_response->forRequest($request)->forUser($this->getUserById($challenged_user));
    }
    
}
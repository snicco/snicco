<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;

class TwoFactorAuthSessionController extends Controller
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
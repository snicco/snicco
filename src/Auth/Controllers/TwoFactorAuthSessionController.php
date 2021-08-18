<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Contracts\EncryptorInterface;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;

class TwoFactorAuthSessionController extends Controller
{
    
    use InteractsWithTwoFactorSecrets;
    use ResolvesUser;
    
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }
    
    public function create(Request $request)
    {
        
        $challenged_user = $request->session()->challengedUser();
        
        if ( ! $challenged_user
             || ! $this->userHasTwoFactorEnabled(
                $this->getUserById($challenged_user)
            )) {
            
            return $this->response_factory->redirect()->toRoute('auth.login');
            
        }
        
        return $this->view_factory->make('auth-layout')->with([
            'view' => 'auth-two-factor-challenge',
            'post_to' => $this->url->toLogin(),
        ]);
        
    }
    
}
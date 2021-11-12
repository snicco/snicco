<?php

declare(strict_types=1);

namespace Snicco\Auth\Middleware;

use Snicco\Http\Delegate;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Events\Login;
use Snicco\Auth\Events\Logout;
use Snicco\Contracts\Middleware;
use Snicco\Auth\AuthSessionManager;
use Psr\Http\Message\ResponseInterface;
use Snicco\Auth\Responses\LoginResponse;
use Snicco\Auth\Responses\LogoutResponse;

class AuthenticateSession extends Middleware
{
    
    protected array            $forget_keys_on_idle;
    private AuthSessionManager $manager;
    
    public function __construct(AuthSessionManager $manager, array $forget_keys_on_idle = [])
    {
        $this->manager = $manager;
        $this->forget_keys_on_idle = $forget_keys_on_idle;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $session = $request->session();
        
        // If persistent login via cookies is enabled we can't invalidate an idle session
        // because this would log the user out.
        // Instead, we just empty out the session which will also trigger every auth confirmation middleware again.
        if ($session->isIdle($this->manager->idleTimeout())) {
            $session->forget('auth.confirm');
            
            foreach ($this->forget_keys_on_idle as $key) {
                $session->forget($key);
            }
        }
        
        $response = $next($request);
        
        if ($response instanceof LogoutResponse) {
            $this->doLogout($session);
        }
        
        if ($response instanceof LoginResponse) {
            $this->doLogin($response, $request);
            
            // We are inside an iframe and just need to close it with js.
            if ($request->session()->boolean('is_interim_login')) {
                $request->session()->forget('is_interim_login');
                return $this->response_factory->view('framework.auth.interim-login-success');
            }
        }
        
        return $response;
    }
    
    private function doLogout(Session $session)
    {
        $user_being_logged_out = $session->userId();
        
        $session->invalidate();
        $session->setUserId(0);
        wp_clear_auth_cookie();
        wp_set_current_user(0);
        
        Logout::dispatch([$session, $user_being_logged_out]);
    }
    
    private function doLogin(LoginResponse $response, Request $request)
    {
        $user = $response->user();
        $remember = $response->shouldRememberUser();
        
        $session = $request->session();
        $session->setUserId($user->ID);
        $session->put(
            'auth.has_remember_token',
            $remember && $this->manager->allowsPersistentLogin()
        );
        $session->confirmAuthUntil($this->manager->confirmationDuration());
        $session->regenerate();
        wp_set_auth_cookie($user->ID, $remember, true);
        wp_set_current_user($user->ID);
        
        Login::dispatch([$user, $remember]);
    }
    
}
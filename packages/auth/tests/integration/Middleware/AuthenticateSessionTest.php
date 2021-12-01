<?php

namespace Tests\Auth\integration\Middleware;

use Tests\Auth\integration\AuthTestCase;

class AuthenticateSessionTest extends AuthTestCase
{
    
    /** @test */
    public function idle_sessions_auth_confirmation_namespace_is_cleared()
    {
        $this->bootApp();
        $this->actingAs($calvin = $this->createAdmin());
        $this->session->put('auth.confirm.foo', 'bar');
        
        $response = $this->get('/');
        $this->assertTrue($response->session()->hasValidAuthConfirmToken());
        $this->assertTrue($response->session()->has('auth.confirm.foo'));
        
        $this->travelIntoFuture($this->config->get('auth.idle') + 1);
        
        $response = $this->get('/');
        $this->assertFalse($response->session()->hasValidAuthConfirmToken());
        $this->assertFalse($response->session()->has('auth.confirm.foo'));
    }
    
}
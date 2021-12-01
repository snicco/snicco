<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Snicco\Session\SessionServiceProvider;
use Snicco\Session\Middleware\VerifyCsrfToken;
use Tests\Codeception\shared\FrameworkTestCase;

class VerifyCsrfTokenTest extends FrameworkTestCase
{
    
    /** @test */
    public function missing_csrf_tokens_will_fail()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf');
        
        $response = $this->post('/csrf', [
            '_token' => 'foobar',
        ]);
        
        $response->assertStatus(419);
    }
    
    /** @test */
    public function save_request_methods_are_not_affected()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf');
        
        $response = $this->get('/csrf', [
            '_token' => 'foobar',
        ]);
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function invalid_csrf_tokens_will_fail()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf');
        $this->withDataInSession(['_token' => 'foobaz']);
        
        $response = $this->post('/csrf', [
            '_token' => 'foobar',
        ]);
        
        $response->assertStatus(419);
    }
    
    /** @test */
    public function matching_csrf_tokens_will_work()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf');
        $this->withDataInSession(['_token' => 'foobar']);
        
        $response = $this->post('/csrf', [
            '_token' => 'foobar',
        ]);
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function csrf_tokens_can_be_accepted_in_the_header()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf');
        $this->withDataInSession(['_token' => 'foobar']);
        
        $response = $this->post('/csrf', [], ['X-CSRF-TOKEN' => 'foobar']);
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function urls_can_be_excluded_from_the_csrf_protection()
    {
        $this->bootApp()->withAddedMiddleware('web', 'csrf')->withAddedConfig(
            'middleware.aliases.csrf',
            TestVerifyCsrfToken::class
        );
        
        $response = $this->post('/csrf');
        
        $response->assertStatus(200);
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
        ];
    }
    
}

class TestVerifyCsrfToken extends VerifyCsrfToken
{
    
    protected array $except = [
        'csrf',
    ];
    
}
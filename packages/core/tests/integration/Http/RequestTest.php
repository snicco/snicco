<?php

declare(strict_types=1);

namespace Tests\Core\integration\Http;

use Tests\Codeception\shared\FrameworkTestCase;

class RequestTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->request = $this->frontendRequest('POST', '/foo');
    }
    
    public function testValidateAjaxNonce()
    {
        $nonce = wp_create_nonce('test_nonce');
        
        $request = $this->request->withParsedBody([
            'update_profile' => $nonce,
        ]);
        
        $this->assertTrue($request->hasValidAjaxNonce('test_nonce', 'update_profile'));
        $this->assertFalse($request->hasValidAjaxNonce('test_nonce_wrong', 'update_profile'));
        $this->assertFalse($request->hasValidAjaxNonce('test_nonce', 'update_user'));
        
        $request = $this->request->withParsedBody([
            'update_user' => $nonce,
        ]);
        
        $this->assertFalse($request->hasValidAjaxNonce('test_nonce', 'update_profile'));
        
        $request = $this->request->withParsedBody([
            'update_profile' => $nonce.'a',
        ]);
        
        $this->assertFalse($request->hasValidAjaxNonce('test_nonce', 'update_profile'));
        
        $request = $this->request->withParsedBody([
            '_ajax_nonce' => $nonce,
        ]);
        
        $this->assertTrue($request->hasValidAjaxNonce('test_nonce'));
        
        $request = $this->request->withParsedBody([
            '_wpnonce' => $nonce,
        ]);
        
        $this->assertTrue($request->hasValidAjaxNonce('test_nonce'));
    }
    
    /** @test */
    public function testAuthenticated()
    {
        $this->assertFalse($this->request->authenticated());
        $this->actingAs($calvin = $this->createAdmin());
        $this->assertTrue($this->request->authenticated());
    }
    
}
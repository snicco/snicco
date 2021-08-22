<?php

declare(strict_types=1);

namespace Tests\integration\Http;

use Tests\FrameworkTestCase;

class RequestTest extends FrameworkTestCase
{
    
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
    
    protected function setUp() :void
    {
        
        parent::setUp();
        
        $this->request = $this->frontendRequest('POST', '/foo');
        
    }
    
}
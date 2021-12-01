<?php

namespace Tests\Core\integration\Http;

use Snicco\Http\ResponseEmitter;
use Snicco\Testing\TestResponseEmitter;
use Tests\Codeception\shared\FrameworkTestCase;

class HttpKernelResponsesTest extends FrameworkTestCase
{
    
    /** @test */
    public function nothing_is_sent_for_a_null_response()
    {
        do_action('init');
        $this->get('/null');
        
        /** @var TestResponseEmitter $response_emitter */
        $response_emitter = $this->app->resolve(ResponseEmitter::class);
        
        $this->assertNull($response_emitter->response);
        $this->assertFalse($response_emitter->sent_headers);
        $this->assertFalse($response_emitter->sent_body);
    }
    
    /** @test */
    public function only_headers_are_sent_for_delegated_response()
    {
        do_action('init');
        $this->get('/delegate');
        
        /** @var TestResponseEmitter $response_emitter */
        $response_emitter = $this->app->resolve(ResponseEmitter::class);
        
        $this->assertTrue($response_emitter->sent_headers);
        $this->assertFalse($response_emitter->sent_body);
    }
    
}
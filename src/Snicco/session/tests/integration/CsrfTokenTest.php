<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Codeception\TestCase\WPTestCase;
use Snicco\Session\ValueObjects\CsrfToken;

final class CsrfTokenTest extends WPTestCase
{
    
    /** @test */
    public function testAsString()
    {
        $csrf_token = new CsrfToken('foobar');
        $this->assertSame('foobar', $csrf_token->asString());
    }
    
    /** @test */
    public function testAsQueryParameter()
    {
        $csrf_token = new CsrfToken('foobar');
        
        $this->assertSame('_token=foobar', $csrf_token->asQueryParameter());
    }
    
    /** @test */
    public function testAsInputField()
    {
        $csrf_token = new CsrfToken('foobar');
        
        $this->assertSame(
            '<input type="hidden" name="_token" value="foobar">',
            $csrf_token->asInputField()
        );
    }
    
    /** @test */
    public function testAsMetaProperty()
    {
        $csrf_token = new CsrfToken('foobar');
        
        $this->assertSame('<meta name="_token" content="foobar">', $csrf_token->asMetaProperty());
    }
    
}
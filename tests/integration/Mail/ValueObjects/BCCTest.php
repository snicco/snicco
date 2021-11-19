<?php

declare(strict_types=1);

namespace Tests\integration\Mail\ValueObjects;

use Snicco\Mail\ValueObjects\BCC;
use Codeception\TestCase\WPTestCase;

final class BCCTest extends WPTestCase
{
    
    /** @test */
    public function testInvalidEmailTriggersWarning()
    {
        $run = 0;
        set_error_handler(function () use (&$run) {
            $args = func_get_args();
            
            $this->assertSame(
                '[@asd.d.e] is not a valid email. Recipient name: [calvin]',
                $args[1]
            );
            $this->assertSame(E_USER_WARNING, $args[0]);
            
            $run++;
        }, E_USER_WARNING);
        
        $bcc = new BCC('@asd.d.e', 'calvin');
        
        restore_error_handler();
        
        $this->assertSame(1, $run);
        $this->assertFalse($bcc->valid());
    }
    
    /** @test */
    public function testGetters()
    {
        $recipient = new BCC('calvin@web.de', 'calvin alkan');
        
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        
        $this->assertSame('Bcc: Calvin Alkan <calvin@web.de>', $recipient->formatted());
        $this->assertSame('Bcc: Calvin Alkan <calvin@web.de>', (string) $recipient);
        $this->assertTrue($recipient->valid());
    }
    
}
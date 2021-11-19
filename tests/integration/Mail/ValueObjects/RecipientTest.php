<?php

declare(strict_types=1);

namespace Tests\integration\Mail\ValueObjects;

use Codeception\TestCase\WPTestCase;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

final class RecipientTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    
    /** @test */
    public function testInvalidEmailTriggersWarning()
    {
        $run = 0;
        set_error_handler(function () use (&$run) {
            $args = func_get_args();
            
            $this->assertSame(
                "[@asd.d.e] is not a valid email. Recipient name: [calvin]",
                $args[1]
            );
            $this->assertSame(E_USER_WARNING, $args[0]);
            
            $run++;
        }, E_USER_WARNING);
        
        $recipient = new Recipient('@asd.d.e', 'calvin');
        
        restore_error_handler();
        
        $this->assertSame(1, $run);
        $this->assertFalse($recipient->valid());
    }
    
    /** @test */
    public function testGetters()
    {
        $recipient = new Recipient('calvin@web.de', 'calvin alkan');
        
        $this->assertSame("Calvin Alkan", $recipient->getName());
        $this->assertSame("calvin@web.de", $recipient->getEmail());
        
        $this->assertSame("Calvin Alkan <calvin@web.de>", $recipient->formatted());
        $this->assertSame("Calvin Alkan <calvin@web.de>", (string) $recipient);
        $this->assertTrue($recipient->valid());
        $this->assertSame('Calvin', $recipient->getFirstName());
        $this->assertSame('Alkan', $recipient->getLastName());
    }
    
    /** @test */
    public function testFromWordPressUser()
    {
        $user = $this->createSubscriber(
            ['user_email' => 'calvin@web.de', 'display_name' => 'calvin alkan']
        );
        
        $recipient = Recipient::fromWPUser($user);
        
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
        $this->assertSame('Calvin Alkan <calvin@web.de>', (string) $recipient);
        $this->assertTrue($recipient->valid());
    }
    
}
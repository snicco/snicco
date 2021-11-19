<?php

declare(strict_types=1);

namespace Tests\integration\Mail\ValueObjects;

use stdClass;
use InvalidArgumentException;
use Snicco\Mail\ValueObjects\CC;
use Snicco\Mail\ValueObjects\BCC;
use Snicco\Mail\ValueObjects\From;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\ReplyTo;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

final class AddressTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    
    /** @test */
    public function testExceptionForNon_Name_classes()
    {
        Address::normalize('calvin@web.de', Recipient::class);
        Address::normalize('calvin@web.de', From::class);
        Address::normalize('calvin@web.de', ReplyTo::class);
        Address::normalize('calvin@web.de', CC::class);
        Address::normalize('calvin@web.de', BCC::class);
        
        $this->expectException(InvalidArgumentException::class);
        Address::normalize('calvin@web.de', self::class);
    }
    
    /** @test */
    public function test_normalize_from_string()
    {
        $recipient = Address::normalize('calvin@web.de', Recipient::class);
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('', $recipient->getName());
        $this->assertSame('calvin@web.de', $recipient->formatted());
    }
    
    /** @test */
    public function test_normalize_from_array()
    {
        $recipient = Address::normalize(
            ['name' => 'Calvin Alkan', 'email' => 'calvin@web.de'],
            Recipient::class
        );
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
    }
    
    /** @test */
    public function testNoExceptionWithMissingNameInArray()
    {
        $recipient = Address::normalize(
            ['email' => 'calvin@web.de', 'Calvin Alkan'],
            Recipient::class
        );
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
    }
    
    /** @test */
    public function testNoExceptionWithMissingEmailInArray()
    {
        $recipient = Address::normalize(
            ['bogus' => 'calvin@web.de', 'name' => 'Calvin Alkan'],
            Recipient::class
        );
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
    }
    
    /** @test */
    public function testFromWPUser()
    {
        $user = $this->createSubscriber(
            ['user_email' => 'calvin@web.de', 'display_name' => 'Calvin Alkan']
        );
        
        $recipient = Address::normalize($user, Recipient::class);
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
    }
    
    /** @test */
    public function testFromObject()
    {
        $object = new stdClass();
        $object->name = 'Calvin Alkan';
        $object->email = 'calvin@web.de';
        
        $recipient = Address::normalize($object, Recipient::class);
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
    }
    
    /** @test */
    public function testFromFormattedString()
    {
        $recipient = Address::normalize('Calvin Alkan <calvin@web.de>', Recipient::class);
        
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin Alkan', $recipient->getName());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $recipient->formatted());
        
        $recipient = Address::normalize('Calvin   <calvin@web.de>', Recipient::class);
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('Calvin', $recipient->getName());
        $this->assertSame('Calvin <calvin@web.de>', $recipient->formatted());
        
        $recipient = Address::normalize('<calvin@web.de>', Recipient::class);
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('', $recipient->getName());
        $this->assertSame('calvin@web.de', $recipient->formatted());
        
        $recipient = Address::normalize('calvin@web.de', Recipient::class);
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('calvin@web.de', $recipient->getEmail());
        $this->assertSame('', $recipient->getName());
        $this->assertSame('calvin@web.de', $recipient->formatted());
    }
    
}
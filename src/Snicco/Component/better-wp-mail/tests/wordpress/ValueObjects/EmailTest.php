<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use LogicException;
use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\ValueObjects\Address;

use function fopen;
use function dirname;
use function file_get_contents;

final class EmailTest extends WPTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->attachment_dir = dirname(__DIR__, 2).'/fixtures';
    }
    
    /** @test */
    public function emails_are_immutable()
    {
        $email = new Email();
        
        $new = $email->withTo('calvin@web.de');
        
        $this->assertNotSame($email, $new);
        
        $this->assertCount(1, $new->getTo());
        $this->assertCount(0, $email->getTo());
    }
    
    /** @test */
    public function test_cc()
    {
        $email = (new Email())->withCc('calvin@web.de');
        
        $this->assertCount(1, $email->getCc());
    }
    
    /** @test */
    public function test_bcc()
    {
        $email = (new Email())->withBcc('calvin@web.de');
        
        $this->assertCount(1, $email->getBcc());
    }
    
    /** @test */
    public function test_subject()
    {
        $email = (new Email())->withSubject('hello');
        
        $this->assertSame('hello', $email->subject());
    }
    
    /** @test */
    public function test_sender()
    {
        $email = new Email();
        
        $this->assertNull($email->sender());
        
        $email = $email->withSender('calvin@web.de');
        
        $this->assertEquals(Address::create('calvin@web.de'), $email->sender());
    }
    
    /** @test */
    public function test_returnPath()
    {
        $email = new Email();
        
        $this->assertNull($email->returnPath());
        
        $email = $email->withReturnPath('calvin@web.de');
        
        $this->assertEquals(Address::create('calvin@web.de'), $email->returnPath());
    }
    
    /** @test */
    public function test_replyTo()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->replyTo());
        
        $email = $email->withReplyTo('calvin@web.de');
        
        $this->assertCount(1, $email->replyTo());
        
        // duplicate address does nothing
        $email = $email->withReplyTo('calvin@web.de');
        $this->assertCount(1, $email->replyTo());
        
        $email = $email->withReplyTo('marlon@web.de');
        $this->assertCount(2, $email->replyTo());
    }
    
    /** @test */
    public function test_from()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->from());
        
        $email = $email->withFrom('calvin@web.de');
        
        $this->assertCount(1, $email->from());
        
        // duplicate address does nothing
        $email = $email->withFrom('calvin@web.de');
        $this->assertCount(1, $email->from());
        
        $email = $email->withFrom('marlon@web.de');
        $this->assertCount(2, $email->from());
    }
    
    /** @test */
    public function test_attach()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $email = $email->withAttachment(
            $this->attachment_dir.'/php-elephant.jpg',
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('attachment', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }
    
    /** @test */
    public function test_attachBinary()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $contents = file_get_contents($this->attachment_dir.'/php-elephant.jpg');
        
        $email = $email->withBinaryAttachment(
            $contents,
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('attachment', $first->disposition());
        $this->assertSame('elephant', $first->name());
        
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $contents = fopen($this->attachment_dir.'/php-elephant.jpg', 'r');
        
        $email = $email->withBinaryAttachment(
            $contents,
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('attachment', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }
    
    /** @test */
    public function test_embed()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $email = $email->withEmbed(
            $this->attachment_dir.'/php-elephant.jpg',
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('inline', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }
    
    /** @test */
    public function test_embedBinary()
    {
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $contents = file_get_contents($this->attachment_dir.'/php-elephant.jpg');
        
        $email = $email->withBinaryEmbed(
            $contents,
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('inline', $first->disposition());
        $this->assertSame('elephant', $first->name());
        
        $email = new Email();
        
        $this->assertCount(0, $email->attachments());
        
        $contents = fopen($this->attachment_dir.'/php-elephant.jpg', 'r');
        
        $email = $email->withBinaryEmbed(
            $contents,
            'elephant',
            'image/jpg'
        );
        
        $this->assertCount(1, $email->attachments());
        
        $first = $email->attachments()[0];
        
        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('inline', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }
    
    /** @test */
    public function test_priority()
    {
        $email = new Email();
        
        $this->assertNull($email->priority());
        
        $email = $email->withPriority(2);
        
        $this->assertSame(2, $email->priority());
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');
        
        $email = $email->withPriority(10);
    }
    
    /** @test */
    public function test_html_template()
    {
        $email = new Email();
        
        $this->assertSame(null, $email->htmlTemplate());
        
        $email = $email->withHtmlTemplate('foobar.php');
        
        $this->assertSame('foobar.php', $email->htmlTemplate());
    }
    
    /** @test */
    public function test_text_template()
    {
        $email = new Email();
        
        $this->assertSame(null, $email->textTemplate());
        
        $email = $email->withTextTemplate('foobar.txt');
        
        $this->assertSame('foobar.txt', $email->textTemplate());
    }
    
    /** @test */
    public function test_htmlBody()
    {
        $email = new Email();
        $this->assertSame(null, $email->htmlBody());
        
        $email = $email->withHtmlBody('<h1>Foo</h1>');
        
        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());
    }
    
    /** @test */
    public function test_textBody()
    {
        $email = new Email();
        $this->assertSame(null, $email->textBody());
        
        $email = $email->withTextBody('Foo');
        
        $this->assertSame('Foo', $email->textBody());
    }
    
    /** @test */
    public function text_body_falls_back_to_html_with_stripped_tags()
    {
        $email = (new Email())->withHtmlBody('<h1>Foo</h1>');
        $this->assertSame('Foo', $email->textBody());
        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());
        
        $email = (new Email())->withHtmlBody('<h1>Foo</h1>')->withTextBody('Foo custom');
        $this->assertSame('Foo custom', $email->textBody());
        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());
    }
    
    /** @test */
    public function test_context()
    {
        $email = new Email();
        
        $this->assertSame([], $email->context());
        
        $email = $email->withContext('foo', 'bar');
        
        $this->assertSame(['foo' => 'bar'], $email->context());
        
        $email = $email->withContext('foo', 'baz');
        
        $this->assertSame(['foo' => 'baz'], $email->context());
        
        $email = $email->withContext('bar', 'biz');
        
        $this->assertSame(['foo' => 'baz', 'bar' => 'biz'], $email->context());
    }
    
    /** @test */
    public function test_custom_headers()
    {
        $email = new Email();
        
        $this->assertSame([], $email->customHeaders());
        
        $email = $email->withCustomHeaders(['X-FOO' => 'BAR']);
        
        $this->assertSame(['X-FOO' => 'BAR'], $email->customHeaders());
        
        $email = $email->withCustomHeaders(['X-BAZ' => 'BIZ']);
        
        $this->assertSame(['X-FOO' => 'BAR', 'X-BAZ' => 'BIZ'], $email->customHeaders());
    }
    
    /** @test */
    public function test_priority_too_low_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');
        
        $email = new Email();
        $email = $email->withPriority(1);
        
        $email->withPriority(0);
    }
    
    /** @test */
    public function test_priority_too_high_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');
        
        $email = new Email();
        $email = $email->withPriority(5);
        $email = $email->withPriority(6);
    }
    
    /** @test */
    public function test_exception_if_settings_images_context()
    {
        $email = new Email();
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[images] is a reserved context key');
        
        $email->withContext('images', 'foo');
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\ValueObjects\Mailbox;

use function dirname;
use function file_get_contents;
use function fopen;

final class EmailTest extends WPTestCase
{

    /**
     * @test
     */
    public function emails_are_immutable(): void
    {
        $email = new Email();

        $new = $email->withTo('calvin@web.de');

        $this->assertNotSame($email, $new);

        $this->assertCount(1, $new->to());
        $this->assertCount(0, $email->to());
    }

    /**
     * @test
     */
    public function test_to(): void
    {
        $email = (new Email())->withTo('calvin@web.de');

        $this->assertCount(1, $cc = $email->to());
        $this->assertTrue($cc->has('calvin@web.de'));

        $email = $email->withTo('marlon@web.de');
        $this->assertCount(1, $cc = $email->to());
        $this->assertTrue($cc->has('marlon@web.de'));

        $email = $email->addTo('jon@web.de');
        $this->assertCount(2, $cc = $email->to());
        $this->assertTrue($cc->has('marlon@web.de'));
        $this->assertTrue($cc->has('jon@web.de'));
    }

    /**
     * @test
     */
    public function test_cc(): void
    {
        $email = (new Email())->withCc('calvin@web.de');

        $this->assertCount(1, $cc = $email->cc());
        $this->assertTrue($cc->has('calvin@web.de'));

        $email = $email->withCc('marlon@web.de');
        $this->assertCount(1, $cc = $email->cc());
        $this->assertTrue($cc->has('marlon@web.de'));

        $email = $email->addCc('jon@web.de');
        $this->assertCount(2, $cc = $email->cc());
        $this->assertTrue($cc->has('marlon@web.de'));
        $this->assertTrue($cc->has('jon@web.de'));
    }

    /**
     * @test
     */
    public function test_bcc(): void
    {
        $email = (new Email())->withBcc('calvin@web.de');

        $this->assertCount(1, $bcc = $email->bcc());
        $this->assertTrue($bcc->has('calvin@web.de'));

        $email = $email->withBcc('marlon@web.de');
        $this->assertCount(1, $bcc = $email->bcc());
        $this->assertTrue($bcc->has('marlon@web.de'));

        $email = $email->addBcc('jon@web.de');
        $this->assertCount(2, $bcc = $email->bcc());
        $this->assertTrue($bcc->has('marlon@web.de'));
        $this->assertTrue($bcc->has('jon@web.de'));
    }

    /**
     * @test
     */
    public function test_subject(): void
    {
        $email = (new Email())->withSubject('hello');

        $this->assertSame('hello', $email->subject());
    }

    /**
     * @test
     */
    public function test_sender(): void
    {
        $email = new Email();

        $this->assertNull($email->sender());

        $email = $email->withSender('calvin@web.de');

        $this->assertEquals(Mailbox::create('calvin@web.de'), $email->sender());
    }

    /**
     * @test
     */
    public function test_returnPath(): void
    {
        $email = new Email();

        $this->assertNull($email->returnPath());

        $email = $email->withReturnPath('calvin@web.de');

        $this->assertEquals(Mailbox::create('calvin@web.de'), $email->returnPath());
    }

    /**
     * @test
     */
    public function test_replyTo(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->replyTo());

        $email = $email->withReplyTo('calvin@web.de');

        $this->assertCount(1, $email->replyTo());

        // duplicate address does nothing
        $email = $email->addReplyTo('calvin@web.de');
        $this->assertCount(1, $email->replyTo());

        $email = $email->addReplyTo('marlon@web.de');
        $this->assertCount(2, $email->replyTo());

        $email = $email->withReplyTo('jon@web.de');
        $this->assertCount(1, $email->replyTo());
    }

    /**
     * @test
     */
    public function test_from(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->from());

        $email = $email->withFrom('calvin@web.de');

        $this->assertCount(1, $email->from());

        // duplicate address does nothing
        $email = $email->addFrom('calvin@web.de');
        $this->assertCount(1, $email->from());

        $email = $email->addFrom('marlon@web.de');
        $this->assertCount(2, $email->from());

        $email = $email->withFrom('foo@web.de');
        $this->assertCount(1, $from = $email->from());
        $this->assertTrue($from->has('foo@web.de'));
    }

    /**
     * @test
     */
    public function test_attach(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->attachments());

        $email = $email->addAttachment(
            $this->attachment_dir . '/php-elephant.jpg',
            'elephant',
            'image/jpg'
        );

        $this->assertCount(1, $email->attachments());

        $first = $email->attachments()[0];

        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('attachment', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }

    /**
     * @test
     */
    public function test_attachBinary(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->attachments());

        $contents = file_get_contents($this->attachment_dir . '/php-elephant.jpg');

        $email = $email->addBinaryAttachment(
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

        $contents = fopen($this->attachment_dir . '/php-elephant.jpg', 'r');

        $email = $email->addBinaryAttachment(
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

    /**
     * @test
     */
    public function test_embed(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->attachments());

        $email = $email->addEmbed(
            $this->attachment_dir . '/php-elephant.jpg',
            'elephant',
            'image/jpg'
        );

        $this->assertCount(1, $email->attachments());

        $first = $email->attachments()[0];

        $this->assertSame('image/jpg', $first->contentType());
        $this->assertSame('inline', $first->disposition());
        $this->assertSame('elephant', $first->name());
    }

    /**
     * @test
     */
    public function test_embedBinary(): void
    {
        $email = new Email();

        $this->assertCount(0, $email->attachments());

        $contents = file_get_contents($this->attachment_dir . '/php-elephant.jpg');

        $email = $email->addBinaryEmbed(
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

        $contents = fopen($this->attachment_dir . '/php-elephant.jpg', 'r');

        $email = $email->addBinaryEmbed(
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

    /**
     * @test
     */
    public function test_priority(): void
    {
        $email = new Email();

        $this->assertNull($email->priority());

        $email = $email->withPriority(2);

        $this->assertSame(2, $email->priority());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');

        $email->withPriority(10);
    }

    /**
     * @test
     */
    public function test_html_template(): void
    {
        $email = new Email();

        $this->assertSame(null, $email->htmlTemplate());

        $email = $email->withHtmlTemplate('foobar.php');

        $this->assertSame('foobar.php', $email->htmlTemplate());
    }

    /**
     * @test
     */
    public function test_text_template(): void
    {
        $email = new Email();

        $this->assertSame(null, $email->textTemplate());

        $email = $email->withTextTemplate('foobar.txt');

        $this->assertSame('foobar.txt', $email->textTemplate());
    }

    /**
     * @test
     */
    public function test_htmlBody(): void
    {
        $email = new Email();
        $this->assertSame(null, $email->htmlBody());

        $email = $email->withHtmlBody('<h1>Foo</h1>');

        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());
    }

    /**
     * @test
     */
    public function test_textBody(): void
    {
        $email = new Email();
        $this->assertSame(null, $email->textBody());

        $email = $email->withTextBody('Foo');

        $this->assertSame('Foo', $email->textBody());
    }

    /**
     * @test
     */
    public function text_body_falls_back_to_html_with_stripped_tags(): void
    {
        $email = (new Email())->withHtmlBody('<h1>Foo</h1>');
        $this->assertSame('Foo', $email->textBody());
        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());

        $email = (new Email())->withHtmlBody('<h1>Foo</h1>')->withTextBody('Foo custom');
        $this->assertSame('Foo custom', $email->textBody());
        $this->assertSame('<h1>Foo</h1>', $email->htmlBody());
    }

    /**
     * @test
     */
    public function test_context(): void
    {
        $email = new Email();

        $this->assertSame([], $email->context());

        $email = $email->addContext('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $email->context());

        $email = $email->addContext('foo', 'baz');

        $this->assertSame(['foo' => 'baz'], $email->context());

        $email = $email->addContext('bar', 'biz');

        $this->assertSame(['foo' => 'baz', 'bar' => 'biz'], $email->context());
    }

    /**
     * @test
     */
    public function test_custom_headers(): void
    {
        $email = new Email();

        $this->assertSame([], $email->customHeaders());

        $email = $email->addCustomHeaders(['X-FOO' => 'BAR']);

        $this->assertSame(['X-FOO' => 'BAR'], $email->customHeaders());

        $email = $email->addCustomHeaders(['X-BAZ' => 'BIZ']);

        $this->assertSame(['X-FOO' => 'BAR', 'X-BAZ' => 'BIZ'], $email->customHeaders());
    }

    /**
     * @test
     */
    public function test_priority_too_low_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');

        $email = new Email();
        $email = $email->withPriority(1);

        $email->withPriority(0);
    }

    /**
     * @test
     */
    public function test_priority_too_high_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$priority must be an integer between 1 and 5.');

        $email = new Email();
        $email = $email->withPriority(5);
        $email->withPriority(6);
    }

    /**
     * @test
     */
    public function test_exception_if_settings_images_context(): void
    {
        $email = new Email();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[images] is a reserved context key');

        $email->addContext('images', 'foo');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->attachment_dir = dirname(__DIR__, 2) . '/fixtures';
    }

}
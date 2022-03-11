<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use LogicException;
use MockPHPMailer;
use Snicco\Component\BetterWPMail\Exception\CantSendEmailWithWPMail;
use Snicco\Component\BetterWPMail\Exception\CouldNotRenderMailContent;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail;
use Snicco\Component\BetterWPMail\Tests\fixtures\NamedViewRenderer;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\MailDefaults;
use WP_Error;
use WP_UnitTest_Factory;
use WP_User;

use function array_merge;
use function array_values;
use function dirname;
use function file_get_contents;
use function sprintf;
use function str_replace;
use function wp_mail;

/**
 * @psalm-suppress PossiblyUndefinedIntArrayOffset
 *
 * @internal
 */
final class MailerTest extends WPTestCase
{
    private string $fixtures_dir;

    private MockPHPMailer $php_mailer;

    protected function setUp(): void
    {
        parent::setUp();
        global $phpmailer;

        $phpmailer = new MockPHPMailer(true);
        $phpmailer->mock_sent = [];
        $this->fixtures_dir = dirname(__DIR__) . '/fixtures';
        $this->php_mailer = $phpmailer;
    }

    /**
     * @test
     */
    public function sending_an_email_works(): void
    {
        $mailer = new Mailer(new WPMailTransport());

        $admin1 = $this->createAdmin([
            'user_email' => 'admin1@web.de',
            'display_name' => 'admin1',
        ]);
        $admin2 = $this->createAdmin([
            'user_email' => 'admin2@web.de',
            'display_name' => 'admin2',
        ]);

        $email = new Email();
        $email = $email->withTo([['c@web.de', 'Calvin Alkan'], ['m@web.de', 'Marlon Alkan']])
            ->withCc([[
                'name' => 'Jon',
                'email' => 'jon@web.de',
            ], ['jane@web.de', 'Jane Doe']])
            ->withBcc([$admin1, $admin2])
            ->withSubject('Hi Calvin')
            ->withHtmlBody('<h1>whats up</h1>')
            ->withFrom('Calvin Alkan <c@from.de>');

        $mailer->send($email);

        $data = $this->getSentMails()[0];
        $header = $data['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>, Marlon Alkan <m@web.de>', $header);
        $this->assertStringContainsString('Cc: Jon <jon@web.de>, Jane Doe <jane@web.de>', $header);
        $this->assertStringContainsString('Bcc: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>', $header);
        $this->assertStringContainsString('From: Calvin Alkan <c@from.de>', $header);

        $this->assertSame('Hi Calvin', $data['subject']);

        $body = $data['body'];

        $this->assertStringStartsWith('This is a multi-part message in MIME format', $body);

        $this->assertStringContainsString('Content-Type: text/plain; charset=us-ascii', $body);
        $this->assertStringContainsString('whats up', $body);

        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $body);
        $this->assertStringContainsString('<h1>whats up</h1>', $body);
    }

    /**
     * @test
     */
    public function default_headers_are_added_if_not_configured_on_the_sending_mail(): void
    {
        $config = new MailDefaults('no-reply@inc.de', 'Calvin INC', 'office@inc.de', 'Office Calvin INC',);

        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer(), null, $config);

        $email = (new Email())->withTo([
            'name' => 'Calvin Alkan',
            'email' => 'c@web.de',
        ])
            ->withTextBody('foo')
            ->withSubject('foo')
            ->addCustomHeaders([
                'X-FOO' => 'BAR',
            ]);

        $mailer->send($email);

        $data = $this->getSentMails()[0];
        $headers = $data['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $headers);
        $this->assertStringContainsString('From: Calvin INC <no-reply@inc.de>', $headers);
        $this->assertStringContainsString('Reply-To: Office Calvin INC <office@inc.de>', $headers);
        $this->assertStringContainsString('X-FOO: BAR', $headers);
    }

    /**
     * @test
     */
    public function multiple_reply_to_addresses_can_be_added(): void
    {
        $mailer = new Mailer();

        $email = (new TestMail())->withTo('client@web.de')
            ->addReplyTo('Calvin Alkan <c@web.de>')
            ->addReplyTo('Marlon Alkan <m@web.de>');

        $mailer->send($email);

        $data = $this->getSentMails()[0];
        $headers = $data['header'];

        $this->assertStringContainsString('To: client@web.de', $headers);
        $this->assertStringContainsString('Reply-To: Calvin Alkan <c@web.de>, Marlon Alkan <m@web.de>', $headers);
    }

    /**
     * @test
     */
    public function plain_text_messages_can_be_sent(): void
    {
        $mailer = new Mailer();

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>')
            ->withSubject('Hello')
            ->withTextBody('PLAIN_TEXT');

        $mailer->send($email);

        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);

        $this->assertStringContainsString('PLAIN_TEXT', $first_email['body']);
    }

    /**
     * @test
     */
    public function plain_text_messaged_can_be_loaded_from_a_file(): void
    {
        $mailer = new Mailer();

        $email = (new TestMail())->withTo('Calvin Alkan <c@web.de>')
            ->withSubject('Hello')
            ->withTextTemplate($this->fixtures_dir . '/plain-text-mail.txt');

        $mailer->send($email);

        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);

        $this->assertStringContainsString("Hello, what's up my man.", $first_email['body']);
    }

    /**
     * @test
     */
    public function a_html_mail_can_be_sent(): void
    {
        $mailer = new Mailer();

        $email = (new Email())->withTo('Calvin Alkan <c@web.de>')
            ->withSubject('Hi')
            ->withHtmlBody('<h1>Hello World</h1>');

        $mailer->send($email);

        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hi', $header);
        $this->assertStringContainsString('Content-Type: multipart/alternative;', $header);

        $this->assertStringContainsString('<h1>Hello World</h1>', $first_email['body']);
        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $first_email['body']);

        $this->assertStringContainsString('Hello World', $first_email['body']);
        $this->assertStringContainsString('Content-Type: text/plain; charset=us-ascii', $first_email['body']);
    }

    /**
     * @test
     */
    public function a_html_email_can_be_created_with_a_template_and_all_context_will_be_passed(): void
    {
        $mailer = new Mailer();

        $email = (new Email())->withTo('Calvin Alkan <c@web.de>')
            ->withSubject('Hello Calvin')
            ->withHtmlTemplate($this->fixtures_dir . '/php-mail.php')
            ->addContext([
                'foo' => 'FOO',
                'baz' => 'BAZ',
            ]);

        $mailer->send($email);

        $first_email = $this->getSentMails()[0];
        $body = $first_email['body'];
        $header = $first_email['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello Calvin', $header);
        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);

        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $body);
        $this->assertStringContainsString(
            '<h1>Hi</h1><p>FOO</p><p>BAR_NOT_AVAILABLE</p><p>BAZ</p>',
            str_replace(["\n", "\r"], '', $body)
        );
    }

    /**
     * @test
     */
    public function by_default_the_settings_from_wordpress_will_be_used_if_no_from_address_is_provided(): void
    {
        $site_name = get_bloginfo('site_name');
        $admin_email = get_bloginfo('admin_email');

        $mailer = new Mailer();

        $mailer->send((new TestMail())->withTo('calvin@web.de'));

        $header = $this->getSentMails()[0]['header'];

        $this->assertStringContainsString("From: {$site_name} <{$admin_email}>", $header);
        $this->assertStringContainsString("Reply-To: {$site_name} <{$admin_email}>", $header);
    }

    /**
     * @test
     */
    public function from_and_reply_to_name_can_be_customized_per_email(): void
    {
        $mailer = new Mailer();

        $email = new Email();
        $email = $email->withTo('client@web.de')
            ->withFrom('Calvin Alkan <calvin@web.de>')
            ->withReplyTo('Marlon Alkan <marlon@web.de>')
            ->withSubject('foo')
            ->withTextBody('bar');

        $mailer->send($email);

        $mail = $this->getSentMails()[0];

        $header = $mail['header'];

        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringContainsString('From: Calvin Alkan <calvin@web.de>', $header);
        $this->assertStringContainsString('Reply-To: Marlon Alkan <marlon@web.de>', $header);
        $this->assertStringContainsString('Subject: foo', $header);
        $this->assertStringStartsWith('bar', $mail['body']);
    }

    /**
     * @test
     */
    public function html_templates_can_be_loaded_with_the_default_renderer(): void
    {
        $mailer = new Mailer();

        $email = (new Email())->withTo('calvin@web.de')
            ->withHtmlTemplate($this->fixtures_dir . '/html-mail.html')
            ->withSubject('foo');

        $mailer->send($email);

        $first_email = $this->getSentMails()[0];

        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $first_email['body']);
        $this->assertStringContainsString('<h1>Hi Calvin</h1>', $first_email['body']);
    }

    /**
     * @test
     */
    public function a_mail_can_be_sent_to_an_array_of_wordpress_users(): void
    {
        $mailer = new Mailer(new WPMailTransport());

        $admin1 = $this->createAdmin([
            'user_email' => 'admin1@web.de',
            'display_name' => 'admin1',
        ]);
        $admin2 = $this->createAdmin([
            'user_email' => 'admin2@web.de',
            'display_name' => 'admin2',
        ]);

        $email = new TestMail();

        $mailer->send($email->withTo([$admin1, $admin2]));

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];

        $this->assertStringContainsString('To: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>', $header);
    }

    /**
     * @test
     */
    public function no_exception_is_thrown_for_emails_without_subject_line(): void
    {
        $mailer = new Mailer();

        $mailer->send((new Email())->withTo('calvin@web.de')->withTextBody('foo'));

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];

        $this->assertStringContainsString('To: calvin@web.de', $header);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_for_empty_bodies(): void
    {
        $mailer = new Mailer();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An email must have a text or an HTML body or attachments.');

        $mailer->send((new Email())->withTo('calvin@web.de'));
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_no_renderer_supports_the_template(): void
    {
        $mailer = new Mailer();

        $this->expectException(CouldNotRenderMailContent::class);

        $email = new Email();
        $email = $email->withHtmlTemplate($this->fixtures_dir . '/mail.foobar-mail');

        $mailer->send($email);
    }

    /**
     * @test
     */
    public function test_with_custom_renderer_chain(): void
    {
        $chain = new AggregateRenderer(new FilesystemRenderer(), new NamedViewRenderer(),);

        $mailer = new Mailer(new WPMailTransport(), $chain);

        $email = new Email();
        $email = $email->withHtmlTemplate($this->fixtures_dir . '/mail.foobar-mail')->withTo('calvin@web.de');

        $mailer->send($email);

        $this->assertCount(1, $this->getSentMails());
        $this->assertStringContainsString($this->fixtures_dir . '/mail.foobar-mail', $this->getSentMails()[0]['body']);
    }

    /**
     * @test
     */
    public function test_with_custom_renderer_does_work_with_multiple_calls(): void
    {
        $chain = new AggregateRenderer(new FilesystemRenderer(), new NamedViewRenderer(),);

        $mailer = new Mailer(new WPMailTransport(), $chain);

        $email = new Email();
        $email = $email->withHtmlTemplate($this->fixtures_dir . '/mail.foobar-mail')
            ->withTo('calvin@web.de');

        $mailer->send($email);
        $mailer->send($email);

        $this->assertCount(2, $this->getSentMails());
        $this->assertStringContainsString($this->fixtures_dir . '/mail.foobar-mail', $this->getSentMails()[0]['body']);
        $this->assertStringContainsString($this->fixtures_dir . '/mail.foobar-mail', $this->getSentMails()[1]['body']);
    }

    /**
     * @test
     */
    public function the_sender_has_priority_over_the_from_name_and_return_path(): void
    {
        $mailer = new Mailer();

        $email = (new Email())->withSender('Calvin Alkan <c@web.de>')
            ->withFrom('Marlon Alkan <m@web.de>')
            ->withReturnPath(['return@company.de', 'My Company'])
            ->withTextBody('foo')
            ->withTo('c@web.de');

        $mailer->send($email);

        $mail = $this->getSentMails()[0];
        $header = $mail['header'];

        $this->assertStringContainsString('From: Calvin Alkan <c@web.de>', $header);

        // PHP Mailer doesnt support return path.
        $this->assertStringNotContainsString('Return-Path: My Company <return@company.de>', $header);

        // PHPMailer does not support multiple from Addresses. And since the sender has a higher priority we take that.
        $this->assertStringNotContainsString('Marlon Alkan', $header);
    }

    /**
     * @test
     */
    public function wp_mail_errors_lead_to_an_exception(): void
    {
        add_action('wp_mail_content_type', function (): void {
            do_action(
                'wp_mail_failed',
                new WP_Error(
                    'wp_mail_failed',
                    'Something went wrong here.',
                    [
                        'to' => 'foo',
                        'subject' => 'bar',
                    ]
                )
            );
        });

        try {
            $mailer = new Mailer();

            $mailer->send((new TestMail())->withTo('calvin@web.de'));

            $this->fail('No exception thrown.');
        } catch (CantSendEmailWithWPMail $e) {
            $this->assertStringStartsWith(
                'wp_mail() failure. Message: [Something went wrong here.]',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_alt_body_and_body_will_be_reset_on_the_php_mailer_instance_if_an_exception_occurs(): void
    {
        global $phpmailer;
        $phpmailer->AltBody = 'foobar';
        $phpmailer->Body = 'baz';

        add_action('wp_mail_content_type', function (): void {
            do_action(
                'wp_mail_failed',
                new WP_Error(
                    'wp_mail_failed',
                    'Something went wrong here.',
                    [
                        'to' => 'foo',
                        'subject' => 'bar',
                    ]
                )
            );
        });

        try {
            $mailer = new Mailer();
            $mailer->send((new TestMail())->withTo('calvin@web.de'));
            $this->fail('No exception thrown.');
        } catch (CantSendEmailWithWPMail $e) {
            $this->assertSame('', $phpmailer->AltBody);
            $this->assertSame('', $phpmailer->Body);
        }
    }

    /**
     * @test
     */
    public function the_priority_is_reset(): void
    {
        $mailer = new Mailer();
        $mailer->send((new TestMail())->withTo('calvin@web.de')->withPriority(5));

        $mail = $this->getSentMails()[0];

        global $phpmailer;
        $this->assertNull($phpmailer->Priority);

        $this->assertStringContainsString('X-Priority: 5', $mail['header']);
    }

    /**
     * @test
     */
    public function a_multipart_email_can_be_sent(): void
    {
        $mailer = new Mailer();

        $email = (new TestMail())->withHtmlBody('<h1>ÜÜ</h1>')
            ->withTextBody('öö')
            ->withTo('calvin@web.de');

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];

        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('boundary=', $header);

        $this->assertStringContainsString('This is a multi-part message in MIME format', $first_mail['body']);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $first_mail['body']);
        $this->assertStringContainsString('öö', $first_mail['body']);

        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $first_mail['body']);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $first_mail['body']);

        global $phpmailer;
        $this->assertSame('', $phpmailer->AltBody);
    }

    /**
     * @test
     */
    public function all_filters_are_unhooked_after_sending_a_mail(): void
    {
        $mailer = new Mailer();
        $email = (new TestMail())->withHtmlBody('<h1>ÜÜ</h1>')
            ->withTextBody('öö')
            ->withTo('calvin@web.de');
        $mailer->send($email);

        wp_mail('marlon@web.de', 'foo', '<h1>bar</h1>', ['Content-Type: text/plain; charset=utf-8']);

        $mails = $this->getSentMails();
        $this->assertCount(2, $mails);

        $second_mail = $mails[1];

        $this->assertStringNotContainsString('Content-Type: multipart/alternative', $second_mail['header']);
        $this->assertStringNotContainsString('öö', $second_mail['body']);

        // Will not throw an exception.
        do_action('wp_mail_failed', new WP_Error());
    }

    /**
     * @test
     */
    public function attachments_can_be_added_from_file_path(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('öö')
            ->withHtmlBody('<h1>ÜÜ</h1>')
            ->addAttachment($this->fixtures_dir . '/php-elephant.jpg', 'my-elephant', 'image/jpeg');

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/mixed', $header);

        $this->assertStringContainsString('Content-Type: multipart/alternative', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $body);

        $this->assertStringContainsString('öö', $body);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $body);

        $this->assertStringContainsString('Content-Type: image/jpeg', $body);
        $this->assertStringContainsString('name=my-elephant', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: attachment;', $body);
        $this->assertStringContainsString('filename=my-elephant', $body);
    }

    /**
     * @test
     */
    public function attachments_can_be_added_as_an_in_memory_string(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('öö')
            ->withHtmlBody('<h1>ÜÜ</h1>')
            ->addBinaryAttachment(
                file_get_contents($this->fixtures_dir . '/php-elephant.jpg'),
                'my-elephant',
                'image/jpeg'
            );

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/mixed', $header);

        $this->assertStringContainsString('Content-Type: multipart/alternative', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $body);

        $this->assertStringContainsString('öö', $body);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $body);

        $this->assertStringContainsString('Content-Type: image/jpeg;', $body);
        $this->assertStringContainsString('name=my-elephant', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: attachment;', $body);
        $this->assertStringContainsString('filename=my-elephant', $body);
    }

    /**
     * @test
     */
    public function attachments_can_be_embedded_by_path(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('öö')
            ->withHtmlBody('<h1>ÜÜ</h1>')
            ->addEmbed($this->fixtures_dir . '/php-elephant.jpg', 'my-elephant', 'image/jpeg');

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('Content-Type: multipart/related', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $body);

        $this->assertStringContainsString('öö', $body);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $body);

        $this->assertStringContainsString('Content-Type: image/jpeg;', $body);
        $this->assertStringContainsString('name=my-elephant', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: inline;', $body);
        $this->assertStringContainsString('filename=my-elephant', $body);

        $expected_cid = $email->attachments()[0]
            ->cid();
        $this->assertStringContainsString("Content-ID: <{$expected_cid}>", $body);
    }

    /**
     * @test
     */
    public function attachments_can_be_embeded_from_memory(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('öö')
            ->withHtmlBody('<h1>ÜÜ</h1>')
            ->addBinaryEmbed(
                file_get_contents($this->fixtures_dir . '/php-elephant.jpg'),
                'my-elephant',
                'image/jpeg'
            );

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('Content-Type: multipart/related', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $body);

        $this->assertStringContainsString('öö', $body);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $body);

        $this->assertStringContainsString('Content-Type: image/jpeg;', $body);
        $this->assertStringContainsString('name=my-elephant', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: inline;', $body);
        $this->assertStringContainsString('filename=my-elephant', $body);

        $expected_cid = $email->attachments()[0]
            ->cid();
        $this->assertStringContainsString("Content-ID: <{$expected_cid}>", $body);
    }

    /**
     * @test
     */
    public function attachments_can_be_combined_inline_and_in_memory(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('öö')
            ->withHtmlBody('<h1>ÜÜ</h1>')
            ->addEmbed($this->fixtures_dir . '/php-elephant.jpg', 'php-elephant-inline', 'image/jpeg')
            ->addAttachment($this->fixtures_dir . '/php-elephant.jpg', 'php-elephant-attachment', 'image/jpeg');

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/mixed', $header);

        $this->assertStringContainsString('Content-Type: multipart/alternative', $body);
        $this->assertStringContainsString('Content-Type: multipart/related', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $body);

        $this->assertStringContainsString('öö', $body);
        $this->assertStringContainsString('<h1>ÜÜ</h1>', $body);

        // Inline
        $this->assertStringContainsString('Content-Type: image/jpeg;', $body);
        $this->assertStringContainsString('name=php-elephant-inline', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: inline;', $body);
        $this->assertStringContainsString('filename=php-elephant-inline', $body);
        $expected_cid = $email->attachments()[0]
            ->cid();
        $this->assertStringContainsString("Content-ID: <{$expected_cid}>", $body);

        // Attachment
        $this->assertStringContainsString('Content-Type: image/jpeg;', $body);
        $this->assertStringContainsString('name=php-elephant-attachment', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: attachment;', $body);
        $this->assertStringContainsString('filename=php-elephant-attachment', $body);
    }

    /**
     * @test
     */
    public function the_cid_gets_passed_into_the_template_for_inline_attachments(): void
    {
        $mailer = new Mailer();

        $email =
            (new TestMail())->withTextBody('öö')
                ->addEmbed($this->fixtures_dir . '/php-elephant.jpg', 'php-elephant-inline')
                ->withHtmlTemplate($this->fixtures_dir . '/inline-attachment.php')
                ->withTo('c@web.de');

        $first_attachment = $email->attachments()[0];

        $mailer->send($email);

        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];

        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('Content-Type: multipart/related', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);

        // us-ascii set by phpmailer because we have no 8-bit chars.
        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $body);

        $this->assertStringContainsString('öö', $body);

        // The CID is random we can't know.
        $this->assertStringContainsString(
            '<h1>Hi</h1><p>Here is your image</p><img src="cid:' . $first_attachment->cid() . '"',
            $body
        );

        $this->assertStringContainsString('Content-Type: application/octet-stream;', $body);
        $this->assertStringContainsString('name=php-elephant-inline', $body);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $body);
        $this->assertStringContainsString('Content-Disposition: inline;', $body);
        $this->assertStringContainsString('filename=php-elephant-inline', $body);
        $expected_cid = $email->attachments()[0]
            ->cid();
        $this->assertStringContainsString("Content-ID: <{$expected_cid}>", $body);
    }

    /**
     * @test
     */
    public function test_exception_if_mail_has_no_to_cc_and_bcc_headers(): void
    {
        $mailer = new Mailer();

        $email = new TestMail();
        $email = $email->withTo('c@web.de')
            ->withTextBody('text');

        $mailer->send($email);

        $email = new TestMail();
        $email = $email->withCc('c@web.de')
            ->withTextBody('text');

        $mailer->send($email);

        $email = new TestMail();
        $email = $email->withBcc('c@web.de')
            ->withTextBody('text');

        $mailer->send($email);

        $this->assertCount(3, $this->getSentMails());

        $email = new TestMail();
        $email = $email->withTextBody('text');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An email must have a "To", "Cc", or "Bcc" header.');
        $mailer->send($email);
    }

    /**
     * @test
     */
    public function a_mail_with_just_attachments_can_be_sent(): void
    {
        $mailer = new Mailer(new WPMailTransport());

        $email = (new Email())->withTo('c@web.de')
            ->addAttachment($this->fixtures_dir . '/php-elephant.jpg', 'my-elephant', 'image/jpeg');

        $mailer->send($email);

        $this->assertCount(1, $mails = $this->getSentMails());

        $first = $mails[0];

        $body = $first['body'];
        $this->assertStringContainsString('name=my-elephant', $body);

        // phpmailer reset, normal mails cant send empty emails.
        $this->assertFalse(wp_mail('calvin@web.de', 'subject', ''));
    }

    /**
     * @test
     */
    public function test_exception_if_renderer_does_not_support_html_template(): void
    {
        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer());

        $email = (new Email())->withHtmlTemplate(__DIR__ . '/test.md');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('The mail template renderer does not support html template [%s]', __DIR__ . '/test.md')
        );

        $mailer->send($email);
    }

    /**
     * @test
     */
    public function test_exception_if_the_renderer_does_not_support_text_template(): void
    {
        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer());

        $email = (new Email())->withTextTemplate(__DIR__ . '/test.md');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('The mail template renderer does not support text template [%s]', __DIR__ . '/test.md')
        );

        $mailer->send($email);
    }

    private function createAdmin(array $data): WP_User
    {
        /** @var WP_UnitTest_Factory $factory */
        $factory = $this->factory();

        $user = $factory->user->create_and_get(array_merge($data, [
            'role' => 'administrator',
        ]));

        if (! $user instanceof WP_User) {
            throw new InvalidArgumentException('Must be WP_USER');
        }

        return $user;
    }

    /**
     * @return list<array{header: string, body:string, subject:string}>
     */
    private function getSentMails(): array
    {
        /** @var array<array{header: string, body:string, subject:string}> $mails */
        $mails = $this->php_mailer->mock_sent;

        return array_values($mails);
    }
}

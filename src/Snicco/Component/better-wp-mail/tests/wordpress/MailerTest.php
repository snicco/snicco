<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use WP_Error;
use MockPHPMailer;
use LogicException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\Mailer\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObjects\MailDefaults;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Bundle\Testing\Concerns\InteractsWithWordpressUsers;
use Snicco\Component\BetterWPMail\Tests\fixtures\NamedViewRenderer;
use Snicco\Component\BetterWPMail\Exceptions\MailRenderingException;
use Snicco\Component\BetterWPMail\Exceptions\WPMailTransportException;

use function dirname;
use function str_replace;
use function file_get_contents;

final class MailerTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    
    private string $fixtures_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        global $phpmailer;
        
        $phpmailer = new MockPHPMailer(true);
        $phpmailer->mock_sent = [];
        $this->fixtures_dir = dirname(__DIR__).'/fixtures';
    }
    
    /** @test */
    public function sending_an_email_works()
    {
        $mailer = new Mailer(new WPMailTransport());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $email = new Email();
        $email = $email->withTo([['c@web.de', 'Calvin Alkan'], ['m@web.de', 'Marlon Alkan']])
                       ->withCc(
                           [['name' => 'Jon', 'email' => 'jon@web.de'], ['jane@web.de', 'Jane Doe']]
                       )
                       ->withBcc([$admin1, $admin2])
                       ->withSubject('Hi Calvin')
                       ->withHtmlBody('<h1>whats up</h1>')
                       ->withFrom('Calvin Alkan <c@from.de>');
        
        $mailer->send($email);
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertStringContainsString(
            'To: Calvin Alkan <c@web.de>, Marlon Alkan <m@web.de>',
            $header
        );
        $this->assertStringContainsString('Cc: Jon <jon@web.de>, Jane Doe <jane@web.de>', $header);
        $this->assertStringContainsString(
            'Bcc: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>',
            $header
        );
        $this->assertStringContainsString(
            'From: Calvin Alkan <c@from.de>',
            $header
        );
        
        $this->assertSame('Hi Calvin', $data['subject']);
        
        $body = $data['body'];
        
        $this->assertStringStartsWith('This is a multi-part message in MIME format', $body);
        
        $this->assertStringContainsString('Content-Type: text/plain; charset=us-ascii', $body);
        $this->assertStringContainsString('whats up', $body);
        
        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $body);
        $this->assertStringContainsString('<h1>whats up</h1>', $body);
    }
    
    /** @test */
    public function default_headers_are_added_if_not_configured_on_the_sending_mail()
    {
        $config = new MailDefaults(
            'no-reply@inc.de',
            'Calvin INC',
            'office@inc.de',
            'Office Calvin INC',
        );
        
        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer(), null, $config);
        
        $email = (new Email())->withTo(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])
                              ->withTextBody('foo')
                              ->withSubject('foo');
        
        $mailer->send($email);
        
        $data = $this->getSentMails()[0];
        $headers = $data['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $headers);
        $this->assertStringContainsString('From: Calvin INC <no-reply@inc.de>', $headers);
        $this->assertStringContainsString('Reply-To: Office Calvin INC <office@inc.de>', $headers);
    }
    
    /** @test */
    public function multiple_reply_to_addresses_can_be_added()
    {
        $mailer = new Mailer();
        
        $email = (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
            'client@web.de'
        )
                                                                                     ->withReplyTo(
                                                                                         'Calvin Alkan <c@web.de>'
                                                                                     )
                                                                                     ->withReplyTo(
                                                                                         'Marlon Alkan <m@web.de>'
                                                                                     );
        
        $mailer->send($email);
        
        $data = $this->getSentMails()[0];
        $headers = $data['header'];
        
        $this->assertStringContainsString('To: client@web.de', $headers);
        $this->assertStringContainsString(
            'Reply-To: Calvin Alkan <c@web.de>, Marlon Alkan <m@web.de>',
            $headers
        );
    }
    
    /** @test */
    public function plain_text_messages_can_be_sent()
    {
        $mailer = new Mailer();
        
        $email = (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        )
                                                                                     ->withSubject(
                                                                                         'Hello'
                                                                                     )
                                                                                     ->withTextBody(
                                                                                         'PLAIN_TEXT'
                                                                                     );
        
        $mailer->send($email);
        
        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);
        
        $this->assertStringContainsString("PLAIN_TEXT", $first_email['body']);
    }
    
    /** @test */
    public function plain_text_messaged_can_be_loaded_from_a_file()
    {
        $mailer = new Mailer();
        
        $email = (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
            'Calvin Alkan <c@web.de>'
        )
                                                                                     ->withSubject(
                                                                                         'Hello'
                                                                                     )
                                                                                     ->withTextTemplate(
                                                                                         $this->fixtures_dir
                                                                                         .'/plain-text-mail.txt'
                                                                                     );
        
        $mailer->send($email);
        
        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);
        
        $this->assertStringContainsString("Hello, what's up my man.", $first_email['body']);
    }
    
    /** @test */
    public function a_html_mail_can_be_sent()
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
        $this->assertStringContainsString(
            'Content-Type: text/html; charset=us-ascii',
            $first_email['body']
        );
        
        $this->assertStringContainsString('Hello World', $first_email['body']);
        $this->assertStringContainsString(
            'Content-Type: text/plain; charset=us-ascii',
            $first_email['body']
        );
    }
    
    /** @test */
    public function a_html_email_can_be_created_with_a_template_and_all_context_will_be_passed()
    {
        $mailer = new Mailer();
        
        $email = (new Email())->withTo('Calvin Alkan <c@web.de>')
                              ->withSubject('Hello Calvin')
                              ->withHtmlTemplate($this->fixtures_dir.'/php-mail.php')
                              ->withContext(['foo' => 'FOO', 'baz' => 'BAZ']);
        
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
            str_replace("\n", '', $body)
        );
    }
    
    /** @test */
    public function by_default_the_settings_from_wordpress_will_be_used_if_no_from_address_is_provided()
    {
        $site_name = get_bloginfo('site_name');
        $admin_email = get_bloginfo('admin_email');
        
        $mailer = new Mailer();
        
        $mailer->send(
            (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
                'calvin@web.de'
            )
        );
        
        $header = $this->getSentMails()[0]['header'];
        
        $this->assertStringContainsString("From: $site_name <$admin_email>", $header);
        $this->assertStringContainsString("Reply-To: $site_name <$admin_email>", $header);
    }
    
    /** @test */
    public function from_and_reply_to_name_can_be_customized_per_email()
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
    
    /** @test */
    public function html_templates_can_be_loaded_with_the_default_renderer()
    {
        $mailer = new Mailer();
        
        $email = (new Email())->withTo('calvin@web.de')
                              ->withHtmlTemplate($this->fixtures_dir.'/html-mail.html')
                              ->withSubject('foo');
        
        $mailer->send($email);
        
        $first_email = $this->getSentMails()[0];
        
        $this->assertStringContainsString(
            'Content-Type: text/html; charset=us-ascii',
            $first_email['body']
        );
        $this->assertStringContainsString('<h1>Hi Calvin</h1>', $first_email['body']);
    }
    
    /** @test */
    public function a_mail_can_be_sent_to_an_array_of_wordpress_users()
    {
        $mailer = new Mailer(new WPMailTransport());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        
        $mailer->send($email->withTo([$admin1, $admin2]));
        
        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        
        $this->assertStringContainsString(
            'To: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>',
            $header
        );
    }
    
    /** @test */
    public function no_exception_is_thrown_for_emails_without_subject_line()
    {
        $mailer = new Mailer();
        
        $mailer->send((new Email())->withTo('calvin@web.de')->withTextBody('foo'));
        
        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        
        $this->assertStringContainsString(
            'To: calvin@web.de',
            $header
        );
    }
    
    /** @test */
    public function an_exception_is_thrown_for_empty_bodies()
    {
        $mailer = new Mailer();
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('An email must have a text or an HTML body or attachments.');
        
        $mailer->send((new Email())->withTo('calvin@web.de'));
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_renderer_supports_the_template()
    {
        $mailer = new Mailer();
        
        $this->expectException(MailRenderingException::class);
        
        $email = new Email();
        $email = $email->withHtmlTemplate($this->fixtures_dir.'/mail.foobar-mail');
        
        $mailer->send($email);
    }
    
    /** @test */
    public function testCustomRendererChain()
    {
        $chain = new AggregateRenderer(
            new NamedViewRenderer(),
        );
        
        $mailer = new Mailer(new WPMailTransport(), $chain);
        
        $email = new Email();
        $email = $email->withHtmlTemplate($this->fixtures_dir.'/mail.foobar-mail')->withTo(
            'calvin@web.de'
        );
        
        $mailer->send($email);
        
        $this->assertCount(1, $this->getSentMails());
    }
    
    /** @test */
    public function the_sender_has_priority_over_the_from_name_and_return_path()
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
        $this->assertStringNotContainsString(
            'Return-Path: My Company <return@company.de>',
            $header
        );
        
        // PHPMailer does not support multiple from Addresses. And since the sender has a higher priority we take that.
        $this->assertStringNotContainsString('Marlon Alkan', $header);
    }
    
    /** @test */
    public function wp_mail_errors_lead_to_an_exception()
    {
        add_action('wp_mail_content_type', function () {
            do_action(
                'wp_mail_failed',
                new WP_Error(
                    'wp_mail_failed',
                    'Something went wrong here.',
                    ['to' => 'foo', 'subject' => 'bar']
                )
            );
        });
        
        try {
            $mailer = new Mailer();
            
            $mailer->send(
                (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
                    'calvin@web.de'
                )
            );
            
            $this->fail('No exception thrown.');
        } catch (WPMailTransportException $e) {
            $this->assertSame(
                'wp_mail() failure. Message: [Something went wrong here.]. Data: [to: foo, subject: bar, ]',
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function the_alt_body_and_body_will_be_reset_on_the_php_mailer_instance_if_an_exception_occurs()
    {
        global $phpmailer;
        $phpmailer->AltBody = 'foobar';
        $phpmailer->Body = 'baz';
        
        add_action('wp_mail_content_type', function () {
            do_action(
                'wp_mail_failed',
                new WP_Error(
                    'wp_mail_failed',
                    'Something went wrong here.',
                    ['to' => 'foo', 'subject' => 'bar']
                )
            );
        });
        
        try {
            $mailer = new Mailer();
            $mailer->send(
                (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
                    'calvin@web.de'
                )
            );
            $this->fail('No exception thrown.');
        } catch (WPMailTransportException $e) {
            $this->assertSame('', $phpmailer->AltBody);
            $this->assertSame('', $phpmailer->Body);
        }
    }
    
    /** @test */
    public function the_priority_is_reset()
    {
        $mailer = new Mailer();
        $mailer->send(
            (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTo(
                'calvin@web.de'
            )->withPriority(5)
        );
        
        $mail = $this->getSentMails()[0];
        
        global $phpmailer;
        $this->assertNull($phpmailer->Priority);
        
        $this->assertStringContainsString('X-Priority: 5', $mail['header']);
    }
    
    /** @test */
    public function a_multipart_email_can_be_sent()
    {
        $mailer = new Mailer();
        
        $email = (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withHtmlBody(
            '<h1>ÜÜ</h1>'
        )
                                                                                     ->withTextBody(
                                                                                         'öö'
                                                                                     )->withTo(
                'calvin@web.de'
            );
        
        $mailer->send($email);
        
        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        
        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('boundary=', $header);
        
        $this->assertStringContainsString(
            'This is a multi-part message in MIME format',
            $first_mail['body']
        );
        $this->assertStringContainsString(
            "Content-Type: text/plain; charset=utf-8",
            $first_mail['body']
        );
        $this->assertStringContainsString("öö", $first_mail['body']);
        
        $this->assertStringContainsString(
            "Content-Type: text/html; charset=utf-8",
            $first_mail['body']
        );
        $this->assertStringContainsString("<h1>ÜÜ</h1>", $first_mail['body']);
        
        global $phpmailer;
        $this->assertSame('', $phpmailer->AltBody);
    }
    
    /** @test */
    public function all_filters_are_unhooked_after_sending_a_mail()
    {
        $mailer = new Mailer();
        $email = (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withHtmlBody(
            '<h1>ÜÜ</h1>'
        )
                                                                                     ->withTextBody(
                                                                                         'öö'
                                                                                     )->withTo(
                'calvin@web.de'
            );
        $mailer->send($email);
        
        wp_mail('marlon@web.de', 'foo', '<h1>bar</h1>', ['Content-Type: text/plain; charset=utf-8']
        );
        
        $mails = $this->getSentMails();
        $this->assertCount(2, $mails);
        
        $second_mail = $mails[1];
        
        $this->assertStringNotContainsString(
            'Content-Type: multipart/alternative',
            $second_mail['header']
        );
        $this->assertStringNotContainsString('öö', $second_mail['body']);
        
        // Will not throw an exception.
        do_action('wp_mail_failed', new WP_Error());
    }
    
    /** @test */
    public function attachments_can_be_added_from_file_path()
    {
        $mailer = new Mailer();
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        $email = $email->withTo('c@web.de')
                       ->withTextBody('öö')
                       ->withHtmlBody('<h1>ÜÜ</h1>')
                       ->withAttachment(
                           $this->fixtures_dir.'/php-elephant.jpg',
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
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=my-elephant\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=my-elephant\n",
            $body
        );
    }
    
    /** @test */
    public function attachments_can_be_added_as_an_in_memory_string()
    {
        $mailer = new Mailer();
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        $email = $email->withTo('c@web.de')
                       ->withTextBody('öö')
                       ->withHtmlBody('<h1>ÜÜ</h1>')
                       ->withBinaryAttachment(
                           file_get_contents($this->fixtures_dir.'/php-elephant.jpg'),
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
        
        // octet-stream because the mail did not provide a content-type specifically.
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=my-elephant\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=my-elephant\n",
            $body
        );
    }
    
    /** @test */
    public function attachments_can_be_embedded_by_path()
    {
        $mailer = new Mailer();
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        $email = $email->withTo('c@web.de')
                       ->withTextBody('öö')
                       ->withHtmlBody('<h1>ÜÜ</h1>')
                       ->withEmbed(
                           $this->fixtures_dir.'/php-elephant.jpg',
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
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=my-elephant\nContent-Transfer-Encoding: base64",
            $body
        );
        $this->assertStringContainsString(
            "Content-Disposition: inline; filename=my-elephant",
            $body
        );
    }
    
    /** @test */
    public function attachments_can_be_embeded_from_memory()
    {
        $mailer = new Mailer();
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        $email = $email->withTo('c@web.de')
                       ->withTextBody('öö')
                       ->withHtmlBody('<h1>ÜÜ</h1>')
                       ->withBinaryEmbed(
                           file_get_contents($this->fixtures_dir.'/php-elephant.jpg'),
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
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=my-elephant\nContent-Transfer-Encoding: base64\nContent-ID: <",
            $body
        );
        $this->assertStringContainsString(
            "Content-Disposition: inline; filename=my-elephant",
            $body
        );
    }
    
    /** @test */
    public function attachments_can_be_combined_inline_and_in_memory()
    {
        $mailer = new Mailer();
        
        $email = new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail();
        $email = $email->withTo('c@web.de')
                       ->withTextBody('öö')
                       ->withHtmlBody('<h1>ÜÜ</h1>')
                       ->withEmbed(
                           $this->fixtures_dir.'/php-elephant.jpg',
                           'php-elephant-inline',
                           'image/jpeg'
                       )
                       ->withAttachment(
                           $this->fixtures_dir.'/php-elephant.jpg',
                           'php-elephant-attachment',
                           'image/jpeg'
                       );
        
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
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=php-elephant-inline\nContent-Transfer-Encoding: base64\nContent-ID: <",
            $body
        );
        $this->assertStringContainsString(
            "Content-Disposition: inline; filename=php-elephant-inline\n",
            $body
        );
        
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=php-elephant-attachment\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=php-elephant-attachment\n",
            $body
        );
    }
    
    /** @test */
    public function the_cid_gets_passed_into_the_template_for_inline_attachments()
    {
        $mailer = new Mailer();
        
        $email =
            (new \Snicco\Component\BetterWPMail\Tests\fixtures\Email\TestMail())->withTextBody('öö')
                                                                                ->withEmbed(
                                                                                    $this->fixtures_dir
                                                                                    .'/php-elephant.jpg',
                                                                                    'php-elephant-inline'
                                                                                )
                                                                                ->withHtmlTemplate(
                                                                                    $this->fixtures_dir
                                                                                    .'/inline-attachment.php'
                                                                                )
                                                                                ->withTo(
                                                                                    'c@web.de'
                                                                                );
        
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
            '<h1>Hi</h1><p>Here is your image</p><img src="cid:'.$first_attachment->cid().'"',
            $body
        );
        $this->assertStringContainsString(
            "Content-Type: application/octet-stream; name=php-elephant-inline\nContent-Transfer-Encoding: base64\nContent-ID: <{$first_attachment->cid()}>",
            $body
        );
        
        $this->assertStringContainsString(
            "Content-Disposition: inline; filename=php-elephant-inline\n",
            $body
        );
    }
    
    private function getSentMails() :array
    {
        global $phpmailer;
        return $phpmailer->mock_sent;
    }
    
}


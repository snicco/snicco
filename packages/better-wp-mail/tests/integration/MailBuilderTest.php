<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\integration;

use WP_Error;
use MockPHPMailer;
use LogicException;
use Snicco\Mail\MailBuilder;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\ValueObjects\MailDefaults;
use Snicco\Mail\Renderer\AggregateRenderer;
use Snicco\Mail\Renderer\FilesystemRenderer;
use Tests\BetterWPMail\fixtures\Emails\PHPMail;
use Tests\BetterWPMail\fixtures\Emails\HtmlMail;
use Snicco\Mail\Exceptions\MailRenderingException;
use Snicco\Mail\Exceptions\WPMailTransportException;
use Tests\BetterWPMail\fixtures\Emails\WelcomeEmail;
use Tests\BetterWPMail\fixtures\Emails\PureHTMLMail;
use Tests\BetterWPMail\fixtures\Emails\PlainTextMail;
use Tests\BetterWPMail\fixtures\Emails\IncorrectMail;
use Tests\BetterWPMail\fixtures\Emails\MultiPartEmail;
use Tests\BetterWPMail\fixtures\Emails\NamedViewEmail;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;
use Tests\BetterWPMail\fixtures\Emails\CustomSenderMail;
use Tests\BetterWPMail\fixtures\Emails\CustomHeaderMail;
use Tests\BetterWPMail\fixtures\Emails\ImageAttachmentMail;
use Tests\BetterWPMail\fixtures\Emails\ReplyToMultipleMail;
use Tests\BetterWPMail\fixtures\Emails\InMemoryAttachmentMail;
use Tests\BetterWPMail\fixtures\Emails\CombinedAttachmentMail;
use Tests\BetterWPMail\fixtures\Emails\PlainTextResourceEmail;
use Tests\BetterWPMail\fixtures\Emails\EmbeddedByPathAttachmentMail;
use Tests\BetterWPMail\fixtures\Emails\InlineAttachmentCIDTemplateMail;
use Tests\BetterWPMail\fixtures\Emails\EmbeddedFromMemoryAttachmentMail;

final class MailBuilderTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    
    protected function setUp() :void
    {
        parent::setUp();
        global $phpmailer;
        
        $phpmailer = new MockPHPMailer(true);
        $phpmailer->mock_sent = [];
    }
    
    /** @test */
    public function sending_an_email_works()
    {
        $mail_builder = new MailBuilder(new WordPressMailer());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $mail_builder->to([['c@web.de', 'Calvin Alkan'], ['m@web.de', 'Marlon Alkan']])
                     ->cc([['name' => 'Jon', 'email' => 'jon@web.de'], ['jane@web.de', 'Jane Doe']])
                     ->bcc([$admin1, $admin2])
                     ->send(new WelcomeEmail('Calvin'));
        
        $data = $this->getSentMails()[0];
        $header = $data['header'];
        
        $this->assertSame('Hi Calvin', $data['subject']);
        $this->assertSame('hey whats up.', trim($data['body'], "\n"));
        $this->assertStringContainsString(
            'To: Calvin Alkan <c@web.de>, Marlon Alkan <m@web.de>',
            $header
        );
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $header);
        $this->assertStringContainsString('Cc: Jon <jon@web.de>, Jane Doe <jane@web.de>', $header);
        $this->assertStringContainsString(
            'Bcc: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>',
            $header
        );
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
        
        $mail_builder =
            new MailBuilder(new WordPressMailer(), new FilesystemRenderer(), null, $config);
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new WelcomeEmail()
        );
        
        $data = $this->getSentMails()[0];
        $headers = $data['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $headers);
        $this->assertStringContainsString('From: Calvin INC <no-reply@inc.de>', $headers);
        $this->assertStringContainsString('Reply-To: Office Calvin INC <office@inc.de>', $headers);
    }
    
    /** @test */
    public function multiple_reply_to_addresses_can_be_added()
    {
        $mail = new MailBuilder();
        
        $mail->to('client@web.de')->send(
            new ReplyToMultipleMail()
        );
        
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
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextMail('PLAIN_TEXT')
        );
        
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
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextMail()
        );
        
        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);
        
        $this->assertStringContainsString("Hello, what's up my man.", $first_email['body']);
    }
    
    /** @test */
    public function plain_text_messages_can_be_a_resource()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextResourceEmail()
        );
        
        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/html', $header);
        
        $this->assertStringContainsString("Hello, what's up my man.", $first_email['body']);
    }
    
    /** @test */
    public function a_html_mail_can_be_sent()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new HtmlMail()
        );
        
        $first_email = $this->getSentMails()[0];
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hi', $header);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $header);
        $this->assertStringNotContainsString("text/plain", $header);
        
        $this->assertStringContainsString('<h1>Hello World</h1>', $first_email['body']);
    }
    
    /** @test */
    public function a_html_email_can_be_created_with_a_template_and_all_public_properties_and_additional_data_are_passed()
    {
        $mail_builder = new MailBuilder();
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PHPMail('FOO', 'BAR')
        );
        
        $first_email = $this->getSentMails()[0];
        
        $this->assertSame(
            '<h1>Hi</h1><p>FOO</p><p>BAR_NOT_AVAILABLE_CAUSE_PRIVATE_PROPERTY</p><p>BAZ</p>',
            str_replace("\n", '', $first_email['body'])
        );
        
        $header = $first_email['header'];
        
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
        $this->assertStringContainsString('Subject: Hello Calvin', $header);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $header);
        $this->assertStringNotContainsString('text/plain', $header);
    }
    
    /** @test */
    public function by_default_the_settings_from_wordpress_will_be_used_if_no_from_address_is_provided()
    {
        $site_name = get_bloginfo('site_name');
        $admin_email = get_bloginfo('admin_email');
        
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextMail('YOOOOOO.')
        );
        
        $header = $this->getSentMails()[0]['header'];
        
        $this->assertStringContainsString("From: $site_name <$admin_email>", $header);
        $this->assertStringContainsString("Reply-To: $site_name <$admin_email>", $header);
    }
    
    /** @test */
    public function from_and_reply_to_name_can_be_customized_per_email()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to('client@web.de')->send(
            new CustomHeaderMail()
        );
        
        $mail = $this->getSentMails()[0];
        
        $header = $mail['header'];
        
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $header);
        $this->assertStringContainsString('From: Calvin Alkan <calvin@web.de>', $header);
        $this->assertStringContainsString('Reply-To: Marlon Alkan <marlon@web.de>', $header);
        $this->assertStringContainsString('Subject: foo', $header);
        $this->assertStringStartsWith('bar', $mail['body']);
    }
    
    /** @test */
    public function mail_messages_can_be_loaded_as_html_with_the_default_renderer()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PureHTMLMail()
        );
        
        $first_email = $this->getSentMails()[0];
        
        $header = $first_email['header'];
        
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $header);
        $this->assertStringContainsString('<h1>Hi Calvin</h1>', $first_email['body']);
    }
    
    /** @test */
    public function a_mail_can_be_sent_to_an_array_of_wordpress_users()
    {
        $mail_builder = new MailBuilder(new WordPressMailer());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $mail_builder->to([$admin1, $admin2])
                     ->send(new WelcomeEmail());
        
        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        
        $this->assertStringContainsString(
            'To: Admin1 <admin1@web.de>, Admin2 <admin2@web.de>',
            $header
        );
    }
    
    /** @test */
    public function testExceptionWhenSubject()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectExceptionMessage(
            sprintf('The mailable [%s] has no subject line.', IncorrectMail::class)
        );
        
        $mail_builder->to('calvin@web.de')->send(
            new IncorrectMail()
        );
    }
    
    /** @test */
    public function testExceptionWhenMessage()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectExceptionMessage('A mailable must have text, html or attachments.');
        
        $mail_builder->to('calvin@web.de')->send(
            new IncorrectMail('subject')
        );
    }
    
    /** @test */
    public function testExceptionWhenCCIsCalledBeforeTo()
    {
        $this->expectException(LogicException::class);
        $mail_builder = new MailBuilder();
        
        $mail_builder->cc('foo@web.de')->to('calvin@web.de')->send(
            new PlainTextMail()
        );
    }
    
    /** @test */
    public function testExceptionWhenBCCisCalledBeforeTo()
    {
        $this->expectException(LogicException::class);
        $mail_builder = new MailBuilder();
        
        $mail_builder->bcc('foo@web.de')->to('calvin@web.de')->send(
            new PlainTextMail()
        );
    }
    
    /** @test */
    public function testExceptionIfNoRendererSupportsTheView()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectException(MailRenderingException::class);
        
        $mail_builder->to('foo@web.de')->cc('calvin@web.de')->send(
            new NamedViewEmail()
        );
    }
    
    /** @test */
    public function testCustomRendererChain()
    {
        $chain = new AggregateRenderer(
            new NamedViewRenderer(),
        );
        
        $mail_builder = new MailBuilder(new WordPressMailer(), $chain);
        
        $mail_builder->to('foo@web.de')->cc('calvin@web.de')->send(
            new NamedViewEmail()
        );
        
        $this->assertCount(1, $this->getSentMails());
    }
    
    /** @test */
    public function the_supporting_renderers_are_cached_for_given_view_name()
    {
        $GLOBALS['renderer_called_times'] = 0;
        
        $chain = new AggregateRenderer(
            new NamedViewRenderer(),
        );
        
        $mail_builder = new MailBuilder(new WordPressMailer(), $chain);
        
        $mail_builder->to([['foo@web.de'], ['bar@web.de']])->cc('calvin@web.de')->send(
            new NamedViewEmail()
        );
        
        $this->assertCount(1, $this->getSentMails());
        $this->assertSame(1, $GLOBALS['renderer_called_times']);
        
        $mail_builder->to([['foo@web.de'], ['bar@web.de']])->cc('calvin@web.de')->send(
            new NamedViewEmail()
        );
        
        $this->assertCount(2, $this->getSentMails());
        $this->assertSame(1, $GLOBALS['renderer_called_times']);
        
        unset($GLOBALS['renderer_called_times']);
    }
    
    /** @test */
    public function testResetEverythingAfterSending()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->cc('jon@web.de')->bcc(
            'jane@web.de'
        )->send(
            new PlainTextMail('PLAIN_TEXT')
        );
        
        $first_mail = $this->getSentMails()[0];
        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $first_mail['header']);
        $this->assertStringContainsString('Cc: jon@web.de', $first_mail['header']);
        $this->assertStringContainsString('Bcc: jane@web.de', $first_mail['header']);
        
        $mail_builder->to(['name' => 'Marlon Alkan', 'email' => 'm@web.de'])->send(
            new PlainTextMail('PLAIN_TEXT')
        );
        
        $second_mail = $this->getSentMails()[1];
        $this->assertStringContainsString('To: Marlon Alkan <m@web.de>', $second_mail['header']);
        $this->assertStringNotContainsString('To: Calvin Alkan <c@web.de>', $second_mail['header']);
        $this->assertStringNotContainsString('Cc: jon@web.de', $second_mail['header']);
        $this->assertStringNotContainsString('Bcc: jane@web.de', $second_mail['header']);
    }
    
    /** @test */
    public function the_sender_has_priority_over_the_from_name_and_return_path()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to('client@web.de')->send(new CustomSenderMail());
        
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
            $mail = new MailBuilder();
            $mail->to('calvin@web.de')->send(new PlainTextMail());
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
            $mail = new MailBuilder();
            $mail->to('calvin@web.de')->send(new PlainTextMail());
            $this->fail('No exception thrown.');
        } catch (WPMailTransportException $e) {
            $this->assertSame('', $phpmailer->AltBody);
            $this->assertSame('', $phpmailer->Body);
        }
    }
    
    /** @test */
    public function the_priority_is_reset()
    {
        $mail = new MailBuilder();
        $mail->to('calvin@web.de')->send(new WelcomeEmail());
        
        global $phpmailer;
        $this->assertNull($phpmailer->Priority);
    }
    
    /** @test */
    public function a_multipart_email_can_be_sent()
    {
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new MultiPartEmail('öö', '<h1>ÜÜ</h1>'));
        
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
    }
    
    /** @test */
    public function the_alt_body_is_reset_after_sending_a_multipart_email()
    {
        global $phpmailer;
        
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new MultiPartEmail('öö', '<h1>ÜÜ</h1>'));
        
        $this->assertSame('', $phpmailer->AltBody);
    }
    
    /** @test */
    public function all_filters_are_unhooked_after_sending_a_mail()
    {
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new MultiPartEmail('öö', '<h1>ÜÜ</h1>'));
        
        wp_mail('calvin@web.de', 'foo', '<h1>bar</h1>', ['Content-Type: text/plain; charset=utf-8']
        );
        
        $second_mail = $this->getSentMails()[1];
        
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
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new ImageAttachmentMail());
        
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
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new InMemoryAttachmentMail());
        
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
            "Content-Type: application/octet-stream; name=php-elephant\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=php-elephant\n",
            $body
        );
    }
    
    /** @test */
    public function attachments_can_be_embedded_by_path()
    {
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new EmbeddedByPathAttachmentMail());
        
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
    public function attachments_can_be_added_from_memory()
    {
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new EmbeddedFromMemoryAttachmentMail());
        
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
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new CombinedAttachmentMail());
        
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
        $mail = new MailBuilder();
        
        $mail->to('calvin@web.de')->send(new InlineAttachmentCIDTemplateMail());
        
        $first_mail = $this->getSentMails()[0];
        $header = $first_mail['header'];
        $body = $first_mail['body'];
        
        $this->assertStringContainsString('Content-Type: multipart/alternative', $header);
        $this->assertStringContainsString('Content-Type: multipart/related', $body);
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $body);
        
        // us-ascii set by phpmailer because we have no 8-bit chars.
        $this->assertStringContainsString('Content-Type: text/html; charset=us-ascii', $body);
        
        $this->assertStringContainsString('öö', $body);
        
        // The CID is random.
        $this->assertStringContainsString(
            '<h1>Hi</h1><p>Here is your image</p><img src="cid:',
            $body
        );
        $this->assertStringContainsString(
            "Content-Type: image/jpeg; name=php-elephant-inline\nContent-Transfer-Encoding: base64\nContent-ID: <",
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

class NamedViewRenderer implements MailRenderer
{
    
    public function getMailContent(string $template_name, array $context = []) :string
    {
        return $template_name;
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        if (isset($GLOBALS['renderer_called_times'])) {
            $val = $GLOBALS['renderer_called_times'];
            $val++;
            $GLOBALS['renderer_called_times'] = $val;
        }
        
        return $extension === 'php' || strpos($view, '.') !== false;
    }
    
}
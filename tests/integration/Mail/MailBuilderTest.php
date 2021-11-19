<?php

declare(strict_types=1);

namespace Tests\integration\Mail;

use LogicException;
use Snicco\Mail\Mailable;
use Snicco\Mail\MailBuilder;
use Snicco\Mail\DefaultConfig;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\Implementations\WordPressMailer;
use Snicco\Mail\Exceptions\MailRenderingException;
use Snicco\Mail\Implementations\AggregateRenderer;
use Snicco\Mail\Implementations\FilesystemRenderer;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

final class MailBuilderTest extends WPTestCase
{
    
    use InteractsWithWordpressUsers;
    
    /**
     * @var array
     */
    private $mail_data = [];
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->mail_data = [];
        add_filter('pre_wp_mail', function ($null, array $wp_mail_input) {
            $this->mail_data[] = $wp_mail_input;
            return true;
        }, 10, 2);
    }
    
    /** @test */
    public function sending_an_email_works()
    {
        $mail_builder = new MailBuilder(new WordPressMailer());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $mail_builder->to(['c@web.de', 'Calvin Alkan'])
                     ->cc([['jon@web.de', 'Jon'], ['jane@web.de', 'Jane Doe']])
                     ->bcc([$admin1, $admin2])
                     ->send(new WelcomeEmail());
        
        $data = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $data['to'][0]);
        $this->assertSame('Hi Calvin Alkan', $data['subject']);
        $this->assertSame('whats up.', $data['message']);
        
        $headers = $data['headers'];
        
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
        $this->assertContains('Cc: Jon <jon@web.de>', $headers);
        $this->assertContains('Cc: Jane Doe <jane@web.de>', $headers);
        $this->assertContains('Bcc: Admin1 <admin1@web.de>', $headers);
        $this->assertContains('Bcc: Admin2 <admin2@web.de>', $headers);
    }
    
    /** @test */
    public function sending_to_multiple_recipients_works()
    {
        $mail_builder = new MailBuilder(new WordPressMailer());
        
        $mail_builder->to([
            ['c@web.de', 'Calvin Alkan'],
            ['m@web.de', 'Marlon Alkan'],
        ])->cc([['jon@web.de', 'Jon'], ['jane@web.de', 'Jane Doe']])->send(new WelcomeEmail());
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $first_email['to'][0]);
        $this->assertSame('Hi Calvin Alkan', $first_email['subject']);
        $this->assertSame('whats up.', $first_email['message']);
        
        $headers = $first_email['headers'];
        
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
        $this->assertContains('Cc: Jon <jon@web.de>', $headers);
        $this->assertContains('Cc: Jane Doe <jane@web.de>', $headers);
        
        $second_email = $this->mail_data[1];
        
        $this->assertSame('Marlon Alkan <m@web.de>', $second_email['to'][0]);
        $this->assertSame('Hi Marlon Alkan', $second_email['subject']);
        $this->assertSame('whats up.', $second_email['message']);
        
        $headers = $second_email['headers'];
        
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
        $this->assertContains('Cc: Jon <jon@web.de>', $headers);
        $this->assertContains('Cc: Jane Doe <jane@web.de>', $headers);
    }
    
    /** @test */
    public function other_headers_are_added()
    {
        $config = new DefaultConfig(
            'Calvin INC.',
            'no-reply@inc.de',
            'Office Calvin INC.',
            'office@inc.de'
        );
        $mail_builder = new MailBuilder(new WordPressMailer(), new FilesystemRenderer(), $config);
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new WelcomeEmail(__FILE__)
        );
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $first_email['to'][0]);
        $this->assertSame('Hi Calvin Alkan', $first_email['subject']);
        $this->assertSame('whats up.', $first_email['message']);
        
        $headers = $first_email['headers'];
        
        $this->assertContains('From: Calvin INC. <no-reply@inc.de>', $headers);
        $this->assertContains('Reply-To: Office Calvin INC. <office@inc.de>', $headers);
        
        $this->assertContains(__FILE__, $first_email['attachments']);
    }
    
    /** @test */
    public function plain_text_messaged_can_be_loaded_from_a_file()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextMail()
        );
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $first_email['to'][0]);
        $this->assertSame('Hello', $first_email['subject']);
        $this->assertSame("Hello, what's up my man.", $first_email['message']);
        
        $headers = $first_email['headers'];
        
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
    }
    
    /** @test */
    public function plain_text_mails_can_be_set_inline()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PlainTextMail('YOOOOOO.')
        );
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $first_email['to'][0]);
        $this->assertSame('Hello', $first_email['subject']);
        $this->assertSame("YOOOOOO.", $first_email['message']);
        
        $headers = $first_email['headers'];
        
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
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
        
        $headers = $this->mail_data[0]['headers'];
        
        $this->assertContains("From: $site_name <$admin_email>", $headers);
        $this->assertContains("Reply-To: $site_name <$admin_email>", $headers);
    }
    
    /** @test */
    public function from_and_reply_to_name_can_be_customized_per_email()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to('client@web.de')->send(new CustomHeaderMail());
        
        $mail = $this->mail_data[0];
        
        $this->assertSame('client@web.de', $mail['to'][0]);
        $this->assertSame('foo', $mail['subject']);
        $this->assertSame('bar', $mail['message']);
        
        $headers = $mail['headers'];
        
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
        $this->assertContains('From: Calvin Alkan <calvin@web.de>', $headers);
        $this->assertContains('Reply-To: Marlon Alkan <marlon@web.de>', $headers);
    }
    
    /** @test */
    public function mail_messages_can_be_loaded_as_html_with_the_default_renderer()
    {
        $mail_builder = new MailBuilder();
        
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'c@web.de'])->send(
            new PureHTMLMail()
        );
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame('Calvin Alkan <c@web.de>', $first_email['to'][0]);
        $this->assertSame('foo', $first_email['subject']);
        $this->assertSame('<h1>Hi Calvin</h1>', $first_email['message']);
        
        $headers = $first_email['headers'];
        
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
    }
    
    /** @test */
    public function mail_views_can_be_loaded_with_php_and_receive_the_public_context()
    {
        $mail_builder = new MailBuilder();
        $mail_builder->to(['name' => 'Calvin Alkan', 'email' => 'calvin@web.de'])->send(
            new PHPMail('FOO', 'BAR')
        );
        
        $first_email = $this->mail_data[0];
        
        $this->assertSame(
            '<h1>Hi Calvin Alkan</h1><p>FOO</p><p>BAR_NOT_AVAILABLE_CAUSE_PRIVATE_PROPERTY</p>',
            str_replace("\n", '', $first_email['message'])
        );
        
        $headers = $first_email['headers'];
        
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
    }
    
    /** @test */
    public function a_mail_can_be_sent_to_an_array_of_wordpress_users()
    {
        $mail_builder = new MailBuilder(new WordPressMailer());
        
        $admin1 = $this->createAdmin(['user_email' => 'admin1@web.de', 'display_name' => 'admin1']);
        $admin2 = $this->createAdmin(['user_email' => 'admin2@web.de', 'display_name' => 'admin2']);
        
        $mail_builder->to([$admin1, $admin2])
                     ->send(new WelcomeEmail());
        
        $first_mail = $this->mail_data[0];
        
        $this->assertSame('Admin1 <admin1@web.de>', $first_mail['to'][0]);
        $this->assertSame('Hi Admin1', $first_mail['subject']);
        $this->assertSame('whats up.', $first_mail['message']);
        
        $second_mail = $this->mail_data[1];
        
        $this->assertSame('Admin2 <admin2@web.de>', $second_mail['to'][0]);
        $this->assertSame('Hi Admin2', $second_mail['subject']);
        $this->assertSame('whats up.', $second_mail['message']);
    }
    
    /** @test */
    public function testExceptionWhenSubject()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectExceptionMessage('The mailable has no subject line.');
        
        $mail_builder->to('calvin@web.de')->send(new IncorrectMail());
    }
    
    /** @test */
    public function testExceptionWhenMessage()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectExceptionMessage('The mailable has no message.');
        
        $mail_builder->to('calvin@web.de')->send(new IncorrectMail('subject'));
    }
    
    /** @test */
    public function testExceptionWhenNoContentType()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectExceptionMessage('The mailable has no content-type.');
        
        $mail_builder->to('calvin@web.de')->send(new IncorrectMail('subject', 'message'));
    }
    
    /** @test */
    public function testExceptionWhenCCIsCalledBeforeTo()
    {
        $this->expectException(LogicException::class);
        $mail_builder = new MailBuilder();
        
        $mail_builder->cc('foo@web.de')->to('calvin@web.de')->send(new PlainTextMail());
    }
    
    /** @test */
    public function testExceptionIfNoRendererSupportsTheView()
    {
        $mail_builder = new MailBuilder();
        
        $this->expectException(MailRenderingException::class);
        
        $mail_builder->to('foo@web.de')->cc('calvin@web.de')->send(new NamedViewMailable());
    }
    
    /** @test */
    public function testCustomRendererChain()
    {
        $chain = new AggregateRenderer(
            new NamedViewRenderer(),
        );
        
        $mail_builder = new MailBuilder(new WordPressMailer(), $chain);
        
        $mail_builder->to('foo@web.de')->cc('calvin@web.de')->send(new NamedViewMailable());
        
        $this->assertCount(1, $this->mail_data);
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
            new NamedViewMailable()
        );
        
        $this->assertCount(2, $this->mail_data);
        
        $this->assertSame(1, $GLOBALS['renderer_called_times']);
        unset($GLOBALS['renderer_called_times']);
    }
    
}

class WelcomeEmail extends Mailable
{
    
    /**
     * @var string|null
     */
    private $file;
    
    public function __construct(string $file = null)
    {
        $this->file = $file;
    }
    
    public function configure(Recipient $recipient) :void
    {
        $this->subject("Hi {$recipient->getName()}")
             ->message("whats up.");
        
        if ($this->file) {
            $this->attach($this->file);
        }
    }
    
}

class PlainTextMail extends Mailable
{
    
    /**
     * @var string|null
     */
    private $_message;
    
    public function __construct(string $_message = null)
    {
        $this->_message = $_message;
    }
    
    public function configure(Recipient $recipient) :void
    {
        $this->subject('Hello');
        if ($this->_message) {
            $this->message($this->_message, 'text/plain');
        }
        else {
            $file = __DIR__.'/fixtures/plain-text-mail.txt';
            $this->text($file);
        }
    }
    
}

class CustomHeaderMail extends Mailable
{
    
    protected $subject = 'foo';
    
    protected $message = 'bar';
    
    protected $content_type = 'text/plain';
    
    public function configure(Recipient $recipient) :void
    {
        $this->from('calvin@web.de', 'Calvin Alkan');
        $this->replyTo('marlon@web.de', 'Marlon Alkan');
    }
    
}

class PureHTMLMail extends Mailable
{
    
    protected $subject = 'foo';
    
    public function configure(Recipient $recipient) :void
    {
        $file = __DIR__.'/fixtures/html-mail.html';
        $this->view($file);
    }
    
}

class PHPMail extends Mailable
{
    
    public    $foo;
    protected $subject = 'foo';
    protected $message = 'bar';
    private   $bar;
    
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function configure(Recipient $recipient) :void
    {
        $this->view(__DIR__.'/fixtures/php-mail.php');
    }
    
}

class IncorrectMail extends Mailable
{
    
    /**
     * @var string|null
     */
    private $m;
    /**
     * @var string|null
     */
    private $s;
    /**
     * @var string|null
     */
    private $c;
    
    public function __construct(string $s = null, string $m = null, string $c = null)
    {
        $this->m = $m;
        $this->s = $s;
        $this->c = $c;
    }
    
    public function configure(Recipient $recipient) :void
    {
        if ($this->m) {
            $this->message($this->m);
        }
        if ($this->s) {
            $this->subject($this->s);
        }
        if ($this->c) {
            $this->content_type = $this->c;
        }
        else {
            $this->content_type = '';
        }
    }
    
}

class NamedViewMailable extends Mailable
{
    
    protected $subject = 'foo';
    
    public function configure(Recipient $recipient) :void
    {
        $this->view('mail.foobar-mail');
    }
    
}

class NamedViewRenderer implements MailRenderer
{
    
    public function getMailContent(string $view, array $context = []) :string
    {
        return $view;
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
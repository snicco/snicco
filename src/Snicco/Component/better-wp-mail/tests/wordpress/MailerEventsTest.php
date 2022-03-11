<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use MockPHPMailer;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\MailEventsUsingWPHooks;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\WelcomeEmail;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\WPMailAPI;

/**
 * @internal
 */
final class MailerEventsTest extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $phpmailer;

        $phpmailer = new MockPHPMailer(true);
        $phpmailer->mock_sent = [];
    }

    /**
     * @test
     */
    public function an_event_is_dispatched_before_an_email_is_sent(): void
    {
        $count = 0;

        add_filter(WelcomeEmail::class, function (SendingEmail $email) use (&$count): void {
            ++$count;
        });

        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer(), $this->getEventDispatcher());

        $email = (new WelcomeEmail())->withTo('c@web.de');

        $mailer->send($email);

        $this->assertSame(1, $count, 'Message event not fired.');

        $data = $this->getSentMails()[0];
        $header = $data['header'];

        $this->assertStringContainsString('To: c@web.de', $header);
    }

    /**
     * @test
     */
    public function the_mail_can_be_customized(): void
    {
        add_filter(
            WelcomeEmail::class,
            function (SendingEmail $event): void {
                $event->email = $event->email->withHtmlBody('Custom Html');
            }
        );

        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer(), $this->getEventDispatcher());

        $email = (new WelcomeEmail())->withTo('c@web.de')
            ->withHtmlBody('foo');
        $mailer->send($email);

        $data = $this->getSentMails()[0];

        $this->assertStringContainsString('Custom Html', $data['body']);
    }

    /**
     * @test
     */
    public function an_event_is_dispatched_after_an_email_is_sent(): void
    {
        $count = 0;
        add_action(EmailWasSent::class, function (EmailWasSent $sent_email) use (&$count): void {
            $this->assertSame('foo', $sent_email->email()->htmlBody());
            $this->assertTrue($sent_email->envelope()->recipients()->has('c@web.de'));
            ++$count;
        });

        $mailer = new Mailer(new WPMailTransport(), new FilesystemRenderer(), $this->getEventDispatcher());

        $email = (new WelcomeEmail())->withTo('Calvin Alkan <c@web.de>')
            ->withHtmlBody('foo');
        $mailer->send($email);

        $this->assertSame(1, $count, 'Message event not fired.');

        $data = $this->getSentMails()[0];
        $header = $data['header'];

        $this->assertStringContainsString('To: Calvin Alkan <c@web.de>', $header);
    }

    private function getEventDispatcher(): MailEventsUsingWPHooks
    {
        return new MailEventsUsingWPHooks(new WPMailAPI());
    }

    private function getSentMails(): array
    {
        global $phpmailer;

        return $phpmailer->mock_sent;
    }
}

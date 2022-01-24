<?php

declare(strict_types=1);

namespace Tests\BetterWPMailBundle\integration;

use Snicco\Component\BetterWPMail\Email;
use Snicco\Component\BetterWPMail\Mailer;
use Tests\BetterWPMailBundle\MailBundleTestCase;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\ValueObjects\Mailbox;
use Snicco\Component\BetterWPMail\Contracts\MailBuilderInterface;

final class MailEventsTest extends MaiLBundleTestCase
{
    
    /** @test */
    public function mail_events_are_dispatched()
    {
        $this->withAddedConfig('mail.from.name', 'Marlon Alkan');
        $this->bootApp();
        
        /** @var Mailer $mail_builder */
        $mail_builder = $this->app[MailBuilderInterface::class];
        
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->dispatcher->assertDispatched(TestMail::class);
        $this->dispatcher->assertDispatched(
            EmailWasSent::class,
            function (EmailWasSent $email_was_sent) {
                return $email_was_sent->getEmail() instanceof TestMail
                       && $email_was_sent->getEnvelope()
                                         ->getSender()
                                         ->getName()
                          === 'Marlon Alkan';
            }
        );
        $this->fake_mailer->assertSent(TestMail::class);
    }
    
    /** @test */
    public function framework_mails_can_be_customized()
    {
        $this->bootApp();
        
        $this->dispatcher->listen(TestMail::class, function (TestMail $mail) {
            $mail->to(Mailbox::create('marlon@web.de'));
        });
        
        $count = 0;
        $this->dispatcher->listen(
            EmailWasSent::class,
            function (EmailWasSent $event) use (&$count) {
                $count++;
            }
        );
        
        /** @var Mailer $mail_builder */
        $mail_builder = $this->app[MailBuilderInterface::class];
        $mail_builder->to('calvin@web.de')->send(new TestMail());
        
        $this->fake_mailer->assertSentTo('marlon@web.de', TestMail::class);
        $this->assertSame(1, $count, "Email sent listener not run.");
    }
    
}

class TestMail extends Email
{
    
    public function configure()
    {
        return $this->subject('foo')->text('bar');
    }
    
}
<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\Mailer;
    use WPEmerge\Events\SendMailEvent;
    use WPEmerge\Mail\PendingMail;
    use WPEmerge\Mail\WordpressMailer;

    class MailServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function the_mailer_can_be_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(WordpressMailer::class, TestApp::resolve(Mailer::class ));


        }

        /** @test */
        public function a_pending_mail_can_be_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(PendingMail::class, TestApp::mail() );

        }

        /** @test */
        public function the_mail_event_is_bound () {

            $this->newTestApp();

            $listeners = TestApp::config('events.listeners');

            $this->assertSame([[WordpressMailer::class, 'send']], $listeners[SendMailEvent::class]);

            $this->assertInstanceOf(PendingMail::class, TestApp::mail() );

        }

    }

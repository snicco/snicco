<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\Mailer;
    use WPEmerge\Events\PendingMail;
    use WPEmerge\Listeners\SendMail;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Mail\WordPressMailer;

    class MailServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function the_mailer_can_be_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(WordPressMailer::class, TestApp::resolve(Mailer::class ));


        }

        /** @test */
        public function a_pending_mail_can_be_resolved_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(MailBuilder::class, TestApp::mail() );

        }

        /** @test */
        public function the_mail_event_is_bound () {

            $this->newTestApp();

            $listeners = TestApp::config('events.listeners');

            $this->assertSame([SendMail::class], $listeners[PendingMail::class]);

            $this->assertInstanceOf(MailBuilder::class, TestApp::mail() );

        }

        /** @test */
        public function the_from_email_setting_defaults_to_the_site_name_and_admin_email () {

            $this->newTestApp();
            $from = TestApp::config('mail.from');

            $this->assertArrayHasKey('name', $from);
            $this->assertArrayHasKey('email', $from);

            $reply_to = TestApp::config('mail.reply_to');
            $this->assertArrayHasKey('name', $reply_to);
            $this->assertArrayHasKey('email', $reply_to);


        }

    }

<?php


    declare(strict_types = 1);


    namespace Tests\integration\Mail;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\SendMailEvent;
    use WPEmerge\Mail\Mailable;

    class SendingMailsTest extends IntegrationTest
    {

        // /** @test */
        public function an_event_gets_dispatched_when_sending_a_mail () {

            $this->newTestApp();

            $mail = TestApp::mail();

            ApplicationEvent::fake([SendMailEvent::class]);

            $mail->send(new TestMail('foo'));

            ApplicationEvent::assertDispatchedTimes(SendMailEvent::class, 1);

        }

    }

    class TestMail extends Mailable{

        /**
         * @var string
         */
        private $foo;

        public function __construct(string $foo)
        {

            $this->foo = $foo;
        }

    }
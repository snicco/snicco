<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\PendingMail;
    use PHPUnit\Framework\Assert as PHPUnit;
    use WPEmerge\Testing\Assertable\AssertableMail;
    use WPEmerge\View\ViewFactory;

    trait InteractsWithMail
    {

        protected function mailFake()
        {

            ApplicationEvent::fake([PendingMail::class]);

            return $this;

        }

        protected function assertMailSent(string $mailable) : AssertableMail
        {

            $fake_dispatcher = ApplicationEvent::dispatcher();

            $fake_dispatcher->assertDispatched(PendingMail::class, function (PendingMail $event) use ($mailable) {

                return $event->mail instanceof $mailable;

            }, "The mail [$mailable] was not sent.");

            $events = $fake_dispatcher->allOfType(PendingMail::class);

            PHPUnit::assertSame(1, $actual = count($events), "The mail [$mailable] was sent [$actual] times.");

            return new AssertableMail($events[0], $this->app->resolve(ViewFactory::class));

        }

        protected function assertMailNotSent(string $mailable)
        {

            $fake_dispatcher = ApplicationEvent::dispatcher();

            $fake_dispatcher->assertNotDispatched(PendingMail::class, function (PendingMail $event) use ($mailable) {

                return $event->mail instanceof $mailable;

            }, "The mail [$mailable] was not supposed to be sent.");


        }

    }
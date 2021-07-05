<?php


    declare(strict_types = 1);


    namespace BetterWP\Testing\Concerns;

    use BetterWpHooks\Testing\FakeDispatcher;
    use BetterWP\Events\Event;
    use BetterWP\Events\PendingMail;
    use PHPUnit\Framework\Assert as PHPUnit;
    use BetterWP\Testing\Assertable\AssertableMail;
    use BetterWP\View\ViewFactory;

    trait InteractsWithMail
    {

        protected function mailFake()
        {

            Event::fake([PendingMail::class]);

            return $this;

        }

        protected function clearSentMails() {
            $fake_dispatcher = Event::dispatcher();
            $fake_dispatcher->clearDispatchedEvents();
            return $this;
        }

        protected function assertMailSent(string $mailable) : AssertableMail
        {

            $fake_dispatcher = Event::dispatcher();

            $this->checkMailWasFaked($fake_dispatcher);


            $fake_dispatcher->assertDispatched(PendingMail::class, function (PendingMail $event) use ($mailable) {

                return $event->mail instanceof $mailable;

            }, "The mail [$mailable] was not sent.");

            $events = $fake_dispatcher->allOfType(PendingMail::class);

            PHPUnit::assertSame(1, $actual = count($events), "The mail [$mailable] was sent [$actual] times.");

            return new AssertableMail($events[0], $this->app->resolve(ViewFactory::class));

        }

        protected function assertMailNotSent(string $mailable)
        {

            $fake_dispatcher = Event::dispatcher();

            $this->checkMailWasFaked($fake_dispatcher);

            $fake_dispatcher->assertNotDispatched(PendingMail::class, function (PendingMail $event) use ($mailable) {

                return $event->mail instanceof $mailable;

            }, "The mail [$mailable] was not supposed to be sent.");


        }

        private function checkMailWasFaked($fake_dispatcher)
        {
            if ( ! $fake_dispatcher instanceof FakeDispatcher ) {
                throw new \LogicException('Mails were not faked. Did you forget to call [$this->mailFake()]?');
            }
        }

    }
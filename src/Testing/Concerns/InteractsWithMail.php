<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use PHPUnit\Framework\ExpectationFailedException;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\PendingMail;
    use PHPUnit\Framework\Assert as PHPUnit;

    trait InteractsWithMail
    {
        protected function mailFake() {

            ApplicationEvent::fake([PendingMail::class]);
            return $this;

        }

        protected function assertMailSent(string $mailable) {

            try {

                ApplicationEvent::assertDispatched(function (PendingMail $event) use ( $mailable) {

                    return $event->mail instanceof $mailable;

                });

            } catch (ExpectationFailedException $e) {

                PHPUnit::fail("The mail [$mailable] was not sent.");

            }



        }

    }
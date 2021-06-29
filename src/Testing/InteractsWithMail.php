<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\PendingMail;

    trait InteractsWithMail
    {
        protected function mailFake() {

            ApplicationEvent::fake([PendingMail::class]);
            return $this;
        }

    }
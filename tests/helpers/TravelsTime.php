<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Carbon\Carbon;
    use Carbon\CarbonImmutable;

    trait TravelsTime
    {

        public function backToPresent() {

            if (class_exists(Carbon::class)) {
                Carbon::setTestNow();
            }

            if (class_exists(CarbonImmutable::class)) {
                CarbonImmutable::setTestNow();
            }

        }

        /** Time travel is always cumulative */
        public function travelIntoFuture(int $seconds) {

            Carbon::setTestNow(Carbon::now()->addSeconds($seconds));

        }

    }
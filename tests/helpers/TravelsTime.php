<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Carbon\Carbon;

    trait TravelsTime
    {

        public function backToPresent() {
            Carbon::setTestNow();
        }

        /** Time travel is always cumulative */
        public function travelIntoFuture(int $seconds) {

            Carbon::setTestNow(Carbon::now()->addSeconds($seconds));

        }

    }
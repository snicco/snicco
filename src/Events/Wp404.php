<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Events\Event;

    class Wp404 extends Event
    {

        public function default() :bool {

            return false;

        }

    }
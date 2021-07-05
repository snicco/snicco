<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Application\ApplicationEvent;

    class Wp404 extends ApplicationEvent
    {

        public function default() :bool {

            return false;

        }

    }
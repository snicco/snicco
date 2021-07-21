<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use Snicco\Events\Event;

    class Wp404 extends Event
    {

        public function default() :bool {

            return false;

        }

    }
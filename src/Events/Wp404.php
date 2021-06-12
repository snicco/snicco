<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;

    class Wp404 extends ApplicationEvent
    {

        public function default() :bool {

            return false;

        }

    }
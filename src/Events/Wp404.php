<?php


    declare(strict_types = 1);


    namespace WPMvc\Events;

    use WPMvc\Application\ApplicationEvent;

    class Wp404 extends ApplicationEvent
    {

        public function default() :bool {

            return false;

        }

    }
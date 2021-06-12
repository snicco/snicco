<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;


    class ShortCircuit404
    {

        public function __invoke() : bool
        {
            return true;

        }

    }
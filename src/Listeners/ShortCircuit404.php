<?php


    declare(strict_types = 1);


    namespace BetterWP\Listeners;


    class ShortCircuit404
    {

        public function __invoke() : bool
        {

            return true;

        }

    }
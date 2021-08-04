<?php


    declare(strict_types = 1);


    namespace Snicco\Listeners;

    /**
     * This event listener will prevent that WordPress handles any 404s before we had a chance
     * to match the URL against our routes.
     */
    class ShortCircuit404
    {

        public function __invoke() : bool
        {

            return true;
        }

    }
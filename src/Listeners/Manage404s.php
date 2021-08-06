<?php


    declare(strict_types = 1);


    namespace Snicco\Listeners;

    use Snicco\Events\Event;
    use Snicco\Events\PreWP404;

    /**
     * This event listener will prevent that WordPress handles any 404s before we had a chance
     * to match the URL against our routes.
     */
    class Manage404s
    {

        public function shortCircuit() : bool
        {

            return true;
        }

        public function check404()
        {

            global $wp;

            Event::forget(PreWP404::class);

            $wp->handle_404();

        }


    }
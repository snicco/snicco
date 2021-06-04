<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;


    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Http\Responses\RedirectResponse;

    class RedirectController
    {

        /**
         * @var AbstractRedirector
         */
        private $redirector;

        public function __construct(AbstractRedirector $redirector)
        {
            $this->redirector = $redirector;
        }

        public function to(...$args) : RedirectResponse
        {

            [ $location, $status_code, $secure, $absolute ] = array_slice($args, -4);

            return $this->redirector->to($location, $status_code, [], $secure, $absolute);


        }

        public function away(...$args) : RedirectResponse
        {

            [ $location, $status_code] = array_slice($args, -2);

            return $this->redirector->away($location, $status_code);


        }

        public function toRoute(...$args) : RedirectResponse
        {

            [ $route, $status_code, $params] = array_slice($args, -3);

            return $this->redirector->toRoute($route, $status_code, $params);


        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use WPEmerge\Routing\UrlGenerator;

    class Redirector
    {

        /**
         * @var UrlGenerator
         */
        private $url_generator;

        public function __construct(UrlGenerator $url_generator)
        {
            $this->url_generator = $url_generator;
        }

    }
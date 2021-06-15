<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Contracts\MagicLink;

    class ArrayMagicLink extends MagicLink
    {

        private $magic_links;

        public function notUsed(string $url) : bool
        {
            return isset($this->magic_links[$url]);
        }

        public function invalidate(string $url)
        {
            unset($this->magic_links[$url]);
        }

        public function gc( ) :bool
        {

        }

        public function create(string $url, int $expires) : string
        {
            // TODO: Implement create() method.
        }

    }
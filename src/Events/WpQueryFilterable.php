<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use Snicco\Http\Psr7\Request;
    use WP;

    class WpQueryFilterable extends Event
    {

        /**
         * @var bool
         */
        public $do_request = true;

        /**
         * @var  Request
         */
        public $server_request;

        /**
         * @var  Request
         */
        public $wp;

        public function __construct(Request $server_request, bool $do_request, WP $wp)
        {

            $this->server_request = $server_request->filtersWpQuery(true);
            $this->do_request = $do_request;
            $this->wp = $wp;

        }

        public function default() : bool
        {

            return $this->do_request;

        }


    }
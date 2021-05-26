<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Request;

    class WpQueryFilterable extends ApplicationEvent
    {
        /**
         * @var array
         */
        private $original_query_vars;

        /**
         * @var \WPEmerge\Http\Psr7\Request
         */
        public $server_request;

        public function __construct( Request $server_request, array $query_vars = [] )
        {

            $this->original_query_vars = $query_vars;

            $this->server_request = $server_request;

        }


        public function default () :array  {

            return $this->original_query_vars;

        }

        public function currentQueryVars () : array
        {

            return $this->original_query_vars;

        }

    }
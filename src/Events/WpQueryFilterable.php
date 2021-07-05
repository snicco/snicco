<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Events\Event;
    use BetterWP\Http\Psr7\Request;

    class WpQueryFilterable extends Event
    {


        /**
         * @var array
         */
        private $original_query_vars;

        /**
         * @var  Request
         */
        public $server_request;

        public function __construct( Request $server_request, array $query_vars = [] )
        {

            $this->original_query_vars = $query_vars;

            $this->server_request = $server_request->filtersWpQuery(true);

        }


        public function default () :array  {

            return $this->original_query_vars;

        }

        public function currentQueryVars () : array
        {

            return $this->original_query_vars;

        }



    }
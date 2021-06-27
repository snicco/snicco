<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Contracts;

    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;

    interface AuthConfirmation
    {

        public function prepare(Request $request) : AuthConfirmation;

        /**
         * @return true|array Return either true for a successful confirmation or an array for error messages
         * that will be flashed to the view.
         */
        public function confirm(Request $request);

        /**
         * return anything that can be converted to a response object.
         * @see ResponseFactory::toResponse()
         */
        public function viewResponse(Request $request );

    }
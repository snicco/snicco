<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;

    interface AuthConfirmation
    {


        /**
         * @return true|array Return either true for a successful confirmation or an array of error messages
         */
        public function confirm(Request $request);

        /**
         * return anything that can be converted to a response object.
         * @see ResponseFactory::toResponse()
         */
        public function viewResponse(Request $request );

    }
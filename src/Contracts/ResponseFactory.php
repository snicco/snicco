<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	use WPEmerge\Http\NullResponse;
    use WPEmerge\Http\RedirectResponse;
    use WPEmerge\Http\Response;

    interface ResponseFactory {

		public function view ( string $view, array $data = [], $status = 200, array $headers = []) : Response;

		public function toResponse ( $response ) : Response;

        /**
         *
         * Create a blank psr7 response object with given status code and reason phrase.
         *
         * @param  int  $status_code
         * @param  string  $reason_phrase
         *
         * @return Response
         */
        public function make(int $status_code, string $reason_phrase = '') : Response;

        /**
         *
         * Create a psr7 response with content type text/html and given status code.
         *
         * @param  string  $html
         * @param  int  $status_code
         *
         * @return Response
         */
        public function html(string $html, int $status_code = 200 ) : Response;


        /**
         *
         * Create a psr7 response with content type application/json and given status code.
         * The content will be be json_encoded by this method.
         *
         * @param  mixed  $content
         * @param  int  $status_code
         *
         * @return Response
         */
        public function json($content, int $status = 200 )  : Response;


         /**
          *
          * Create a null response with status code 204.
          *
          * @return NullResponse
          */
        public function null() : NullResponse;

        public function redirect() : RedirectResponse;


    }
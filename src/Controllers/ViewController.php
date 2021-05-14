<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Controllers;


    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Response;

    class ViewController {

		/**
		 * @var Response
		 */
		private $response;

		public function __construct( ResponseFactory $response ) {

			$this->response = $response;

		}

		public function handle( ...$args ) : Response {

			[$view, $data, $status, $headers] = array_slice($args, -4);

			return $this->response->view( $view, $data, $status, $headers );

		}

	}
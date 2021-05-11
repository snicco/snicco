<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Controllers;

	use WPEmerge\Contracts\ResponseFactoryInterface as Response;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;

	class ViewController {

		/**
		 * @var \WPEmerge\Contracts\ResponseFactoryInterface
		 */
		private $response;

		public function __construct( Response $response ) {

			$this->response = $response;

		}

		public function handle( ...$args ) : ResponseInterface {

			[$view, $data, $status, $headers] = array_slice($args, -4);

			return $this->response->view( $view, $data, $status, $headers );

		}

	}
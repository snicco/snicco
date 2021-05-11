<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;

	class BodySent extends ApplicationEvent {


		/**
		 * @var ResponseInterface
		 */
		public $response;

		/**
		 * @var \WPEmerge\Contracts\RequestInterface
		 */
		public $request;

		public function __construct(ResponseInterface $response, RequestInterface $request ) {

			$this->response = $response;
			$this->request = $request;

		}


	}
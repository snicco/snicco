<?php


	namespace WPEmerge\Events;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;

	class BodySent extends ApplicationEvent {


		/**
		 * @var \Psr\Http\Message\ResponseInterface
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
<?php


	namespace WPEmerge\Http;

	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewServiceInterface;

	class ResponseFactory implements ResponseFactoryInterface {

		/**
		 * @var \WPEmerge\Contracts\ViewServiceInterface
		 */
		private $view;

		public function __construct(ViewServiceInterface $view ) {

			$this->view = $view;

		}

		public function view( string $view, array $data = [], $status = 200, array $headers = [] ) : ResponseInterface {

			$content = $this->view->make($view)->with($data)->toString();

			return  ( new Response( $content, $status, $headers ) )->setType('text/html');


		}

	}
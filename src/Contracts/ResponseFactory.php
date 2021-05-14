<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	use WPEmerge\Http\Response;

    interface ResponseFactory {

		public function view ( string $view, array $data = [], $status = 200, array $headers = []) : Response;

		public function prepareResponse ( $response ) : Response;

	}
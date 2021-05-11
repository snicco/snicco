<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface ResponseInterface {


		public function setType(string $type) : ResponseInterface;

		public function setBody ( ?string $content ) : ResponseInterface;

		public function body (): string;

		public function status () :int;

		public function header( $name );

		public function prepareForSending ( RequestInterface $request) : ResponseInterface;

		public function sendHeaders();

		public function sendBody();

	}
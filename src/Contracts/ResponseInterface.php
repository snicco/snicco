<?php


	namespace WPEmerge\Contracts;

	interface ResponseInterface {


		public function setType(string $type) : ResponseInterface;

		public function setBody ( ?string $content ) : ResponseInterface;

		public function body (): string;

		public function status () :int;

		public function header( $name );

	}
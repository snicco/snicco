<?php


	namespace Tests;

	use WPEmerge\Requests\Request;

	class TestRequest extends Request {

		public function __construct(
			$method,
			$uri,
			array $headers = [],
			$body = null,
			$version = '1.1',
			array $serverParams = []
		) {

			parent::__construct( $method, $uri, $headers, $body, $version, $serverParams );

		}

		public static function from(string $method, $path, $host = null ) : TestRequest {

			$path = trim($path, '/') . '/' ;
			$method = strtoupper($method);

			$host = $host ?? 'https://foo.com';

			$url = trim($host, '/') . '/' . $path;

			return new static( $method, $url );


		}


	}
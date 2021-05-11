<?php


	declare( strict_types = 1 );


	namespace Tests;

	use WPEmerge\Http\Request;

	class TestRequest extends Request {

		public $body;

		public static function fromFullUrl(string $method, string $url ) {

			$request = static::create($url, $method);

			return $request;

		}

		public static function from(string $method, $path, $host = null ) : TestRequest {

			$path = trim($path, '/') ?: '/';
			$method = strtoupper($method);

			$host = $host ?? 'https://foo.com';

			$url = trim($host, '/') . '/' . $path;

			$url = trim($url, '/') . '/';

			$request =  static::create($url, $method);

			return $request;

		}

		public function simulateAjax () :TestRequest {

			$this->headers->set( 'X-Requested-With', 'XMLHttpRequest' );

			return $this;

		}

		public function setHeader ( $name , $value ) : TestRequest {

			$this->headers->set($name , $value);

			return $this;

		}

	}
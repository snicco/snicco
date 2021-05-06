<?php


	namespace Tests;

	use WPEmerge\Request;

	class TestRequest extends Request {

		public $body;


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


	}
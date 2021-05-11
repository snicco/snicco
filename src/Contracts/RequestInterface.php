<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	interface RequestInterface {


		public static function capture () :RequestInterface;

		/**
		 * Returns the url without query params
		 *
		 * @return string
		 *
		 */
		public function url() :string;

		/**
		 * Returns the url with query params
		 *
		 * @return string
		 *
		 */
		public function fullUrl() : string;


		public function method() :string;

		/**
		 * Get the current path info for the request.
		 *
		 * @return string
		 */
		public function path() :string;

		/**
		 * Check if the request method is GET.
		 *
		 * @return bool
		 */
		public function isGet() : bool;

		/**
		 * Check if the request method is HEAD.
		 *
		 * @return bool
		 */
		public function isHead() : bool;

		/**
		 * Check if the request method is POST.
		 *
		 * @return bool
		 */
		public function isPost() : bool;

		/**
		 * Check if the request method is PUT.
		 *
		 * @return bool
		 */
		public function isPut() : bool;

		/**
		 * Check if the request method is PATCH.
		 *
		 * @return bool
		 */
		public function isPatch() : bool;

		/**
		 * Check if the request method is DELETE.
		 *
		 * @return bool
		 */
		public function isDelete() : bool;

		/**
		 * Check if the request method is OPTIONS.
		 *
		 * @return bool
		 */
		public function isOptions() : bool;

		/**
		 * Check if the request method is a "read" verb.
		 *
		 * @return bool
		 */
		public function isReadVerb() : bool;

		/**
		 * Check if the request is an ajax request.
		 *
		 * @return bool
		 */
		public function isAjax() : bool;

		/**
		 * Check if the request is the result of a PJAX call.
		 *
		 * @return bool
		 */
		public function isPJAX() : bool;

		/**
		 * Get a value from the request attributes.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function attribute( string $key = '', $default = null );

		/**
		 * Get a value from the request query (i.e. $_GET).
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function query( string $key = '', $default = null );

		/**
		 * Get a value from the request body (i.e. $_POST).
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function body( string $key = '', $default = null );

		/**
		 * Get a value from the COOKIE parameters.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function cookies( string $key = '', $default = null );

		/**
		 * Get a value from the FILES parameters.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function files( string $key = '', $default = null );

		/**
		 * Get a value from the SERVER parameters.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function server( string $key = '', $default = null );

		/**
		 * Get a value from the headers.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function headers( string $key = '', $default = null );

		public function scheme() :string;

		public function setRoute( RouteCondition $route );

		public function setType ( string $request_event): void;

		public function type() : string;

		public function route() : ?RouteCondition;

		public function expectsJson() :bool;

	}

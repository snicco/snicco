<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Closure;

	interface Session {

		/**
		 * Get the name of the session.
		 *
		 * @return string
		 */
		public function getName() : string;

		/**
		 * Set the name of the session.
		 *
		 * @param  string  $name
		 *
		 * @return void
		 */
		public function setName( string $name ) :void;

		/**
		 * Get the current session ID.
		 *
		 * @return string
		 */
		public function getId() : string;

		/**
		 * Set the session ID.
		 *
		 * @param  string  $id
		 *
		 * @return void
		 */
		public function setId( string $id ) :void;

		/**
		 * Start the session, reading the data from a handler.
		 *
		 * @return bool
		 */
		public function start() :bool;

		/**
		 * Save the session data to storage.
		 *
		 * @return void
		 */
		public function save() :void;

		/**
		 * Get all of the session data.
		 *
		 * @return array
		 */
		public function all() : array;

		/**
		 *
		 * Get a subset of the session data
		 *
		 * @param  string|string[]  $keys
		 *
		 * @return array
		 */
		public function only( $keys ) : array;

		/**
		 * Checks if a key exists. If multiple keys are passed
		 * the absence of one key should return false for the function
		 *
		 * @param  string|array  $key
		 *
		 * @return bool
		 */
		public function exists( $key ) : bool;

		/**
		 * Checks if a key is missing.
		 *
		 * @param  string|array  $key
		 *
		 * @return bool
		 */
		public function missing( $key ) : bool;

		/**
		 * Checks if a key is present and not null.
		 *
		 * @param  string|string[]  $key
		 *
		 * @return bool
		 */
		public function has( $key ) : bool;

		/**
		 * Get an item from the session.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function get( string $key, $default = null );

		/**
		 * Get the value of a given key and then forget it.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function pull( string $key, $default = null );

		/**
		 * Put a key / value pair or array of key / value pairs in the session.
		 *
		 * @param  string|string[]  $key
		 * @param  mixed  $value
		 *
		 * @return void
		 */
		public function put( string $key, $value = null ) :void;


		/**
		 * Remove an item from the session, returning its value.
		 *
		 * @param  string  $key
		 *
		 * @return mixed
		 */
		public function remove( string $key );

		/**
		 * Remove one or many items from the session.
		 *
		 * @param  string|string[]  $keys
		 *
		 * @return void
		 */
		public function forget( $keys ) : void;

		/**
		 * Remove all of the items from the session.
		 *
		 * @return void
		 */
		public function flush() : void;

		/**
		 * Flush the session data and regenerate the ID.
		 *
		 * @return bool
		 */
		public function invalidate() : bool;

		/**
		 * Generate a new session identifier.
		 *
		 * @param  bool  $destroy
		 *
		 * @return bool
		 */
		public function regenerate( bool $destroy = false ) : bool;

		/**
		 * Generate a new session ID for the session.
		 *
		 * @param  bool  $destroy
		 *
		 * @return bool
		 */
		public function migrate( bool $destroy = false ) : bool;

		/**
		 * Determine if the session has been started.
		 *
		 * @return bool
		 */
		public function isStarted() : bool;


		/**
		 * Determine if this is a valid session ID.
		 *
		 * @param  string  $id
		 *
		 * @return bool
		 */
		public function isValidId( string $id ) :bool;


		/**
		 * Get the previous URL from the session.
		 *
		 * @return string|null
		 */
		public function previousUrl() : ?string;

		/**
		 * Set the "previous" URL in the session.
		 *
		 * @param  string  $url
		 *
		 * @return void
		 */
		public function setPreviousUrl( string $url ) : void;

		/**
		 * Get the session handler instance.
		 *
		 * @return \SessionHandlerInterface
		 */
		public function getHandler() : \SessionHandlerInterface;

		/**
		 * Determine if the session contains old input.
		 *
		 * @param  string|null  $key
		 *
		 * @return bool
		 */
		public function hasOldInput( string $key = null ) : bool;

		/**
		 * Get the requested item from the flashed input array.
		 *
		 * @param  string|null  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function getOldInput( string $key = null, $default = null );

		/**
		 * Replace the given session attributes entirely.
		 *
		 * @param  array  $attributes
		 *
		 * @return void
		 */
		public function replace( array $attributes ) : void;

		/**
		 * Get an item from the session, or store the default value.
		 *
		 * @param  string  $key
		 * @param  Closure  $callback
		 *
		 * @return mixed
		 */
		public function remember( string $key, Closure $callback );


		/**
		 * Push a value onto a session array.
		 *
		 * @param  string  $key
		 * @param  mixed  $value
		 *
		 * @return void
		 */
		public function push( string $key, $value ) : void;

		/**
		 * Increment the value of an item in the session.
		 *
		 * @param  string  $key
		 * @param  int  $amount
		 *
		 * @return mixed
		 */
		public function increment( string $key, int $amount = 1 ) : int;

		/**
		 * Decrement the value of an item in the session.
		 *
		 * @param  string  $key
		 * @param  int  $amount
		 *
		 * @return int
		 */
		public function decrement( string $key, int $amount = 1 ) : int;

		/**
		 * Flash a key / value pair to the session.
		 *
		 * @param  string  $key
		 * @param  mixed  $value
		 *
		 * @return void
		 */
		public function flash( string $key, $value = true ) : void;

		/**
		 * Flash a key / value pair to the session for immediate use.
		 *
		 * @param  string  $key
		 * @param  mixed  $value
		 *
		 * @return void
		 */
		public function now( string $key, $value ) : void;

		/**
		 * Reflash all of the session flash data.
		 *
		 * @return void
		 */
		public function reflash() : void;

		/**
		 * Reflash a subset of the current flash data.
		 *
		 * @param  array|mixed  $keys
		 *
		 * @return void
		 */
		public function keep( $keys = null ) : void;

		/**
		 * Flash an input array to the old input session key
		 *
		 * @param  array  $value
		 *
		 * @return void
		 */
		public function flashInput( array $value ) : void;

	}
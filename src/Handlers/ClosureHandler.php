<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use WPEmerge\Contracts\RouteHandler;

	class ClosureHandler implements RouteHandler {

		/**
		 * @var \Closure
		 */
		private $resolves_to;
		/**
		 * @var \Closure
		 */
		private $executable_closure;

		/**
		 * @param  \Closure  $raw_closure
		 * @param  \Closure  $executable_closure
		 */
		public function __construct( Closure $raw_closure, Closure $executable_closure) {

			$this->resolves_to = $raw_closure;
			$this->executable_closure = $executable_closure;

		}

		public function executeUsing(...$args) {

			$closure = $this->executable_closure;

			return $closure(...$args);


		}

		public function raw () {

			return $this->resolves_to;

		}

		public function middleware() : array {

			return [];

		}

	}
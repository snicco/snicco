<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use WPEmerge\Contracts\RouteHandler;

	class Controller implements RouteHandler {


		/**
		 * @var array
		 */
		private $raw_callable;
		/**
		 * @var \Closure
		 */
		private $executable_callable;

		public function __construct(array $raw_callable, Closure $executable_callable) {

			$this->raw_callable = $raw_callable;
			$this->executable_callable = $executable_callable;
		}


		public function executeUsing(...$args) {

			$callable = $this->executable_callable;

			return $callable(...$args);

		}

	}
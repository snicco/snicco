<?php


	namespace WPEmerge\Routing;

	class CompiledRoute {

		private $action;

		private $middleware;

		private $conditions;

		public function __construct( $action, $middleware, $conditions ) {

			$this->action     = $action;
			$this->middleware = $middleware;
			$this->conditions = $conditions;

		}

		public static function __set_state( $cached_array ) {

			return new self(
				$cached_array['action'],
				$cached_array['middleware'],
				$cached_array['conditions']
			);

		}



	}
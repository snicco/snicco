<?php


	namespace WPEmerge\Traits;

	trait CompilesExecutableRoute {

		public static function __set_state( $array ) {

			$route = new self($array['methods'], $array['url'], $array['action']);

			return $route;

		}

	}
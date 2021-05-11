<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface CreatesCacheableRoutes {

		public function getRouteMap() :array;

	}
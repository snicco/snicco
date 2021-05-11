<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface RouteAction extends Handler {


		public function raw();


	}
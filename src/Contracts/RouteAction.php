<?php


	declare( strict_types = 1 );


	namespace WPMvc\Contracts;

	interface RouteAction extends Handler {

		public function raw();

	}
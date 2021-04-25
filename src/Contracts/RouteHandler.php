<?php


	namespace WPEmerge\Contracts;

	interface RouteHandler {


		public function executeUsing(...$args);

		public function raw();


	}
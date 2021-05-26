<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

    /**
     * The most generic type of object that will be resolved from the container
     * and wrapped in an executable closure for later usage.
     */
	interface Handler {

		public function executeUsing(...$args);

	}
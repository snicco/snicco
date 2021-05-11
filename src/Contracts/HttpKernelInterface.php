<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RouteCondition as Route;

	/**
	 * Describes how a request is handled.
	 */
	interface HttpKernelInterface extends HasMiddlewareDefinitionsInterface {

		public function handle( RequestInterface $request) :void;

	}

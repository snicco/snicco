<?php



	namespace WPEmerge\Contracts;

	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\RouteInterface as Route;

	/**
	 * Describes how a request is handled.
	 */
	interface HttpKernelInterface extends HasMiddlewareDefinitionsInterface {

		/**
		 * Bootstrap the kernel.
		 *
		 * @return void
		 */
		public function bootstrap();

		/**
		 * Run a response pipeline for the given request.
		 *
		 * @param  Route  $route
		 *
		 * @return ResponseInterface
		 */
		public function run( Route $route  );

		/**
		 * Return a response for the given request.
		 *
		 * @param  RequestInterface  $request
		 * @param  array  $arguments
		 *
		 * @return ResponseInterface|null
		 */
		public function handleRequest( RequestInterface $request, array $arguments = [] );

	}

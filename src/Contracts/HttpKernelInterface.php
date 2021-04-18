<?php



	namespace WPEmerge\Contracts;

	use Closure;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Helpers\Handler;
	use WPEmerge\Contracts\HasMiddlewareDefinitionsInterface;
	use WPEmerge\Contracts\RequestInterface;

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
		 * @param  RequestInterface  $request
		 * @param  string[]  $middleware
		 * @param  string|Closure|Handler  $handler
		 * @param  array  $arguments
		 *
		 * @return ResponseInterface
		 */
		public function run( RequestInterface $request, $middleware, $handler, $arguments = [] );

		/**
		 * Return a response for the given request.
		 *
		 * @param  RequestInterface  $request
		 * @param  array  $arguments
		 *
		 * @return ResponseInterface|null
		 */
		public function handle( RequestInterface $request, $arguments = [] );

	}

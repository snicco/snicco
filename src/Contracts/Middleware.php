<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Delegate;

    abstract class Middleware implements MiddlewareInterface {

        /**
         * @param  Request  $request
         * @param  Delegate $next This class can be called as a closure. $next($request)
         *
         * @return ResponseInterface
         */
        abstract public function handle ( Request $request, Delegate $next );

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {

            return $this->handle($request, $handler);

        }

	}
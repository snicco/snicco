<?php


	declare( strict_types = 1 );


	namespace BetterWP\Contracts;

	use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\ResponseFactory;

    abstract class Middleware implements MiddlewareInterface {


        /** @var ResponseFactory */
        protected $response_factory;

        public function setResponseFactory(ResponseFactory $response_factory) {
            $this->response_factory = $response_factory;
        }

        /**
         * @param  Request  $request
         * @param  Delegate $next This class can be called as a closure. $next($request)
         *
         * @return ResponseInterface
         */
        abstract public function handle ( Request $request, Delegate $next ) :ResponseInterface;

        /**
         * @param  Request  $request
         * @param  RequestHandlerInterface  $handler
         *
         * @return ResponseInterface
         */
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {

            return $this->handle($request, $handler);

        }

	}
<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;

	class WebMiddleware implements MiddlewareInterface {

		const run_times = 'web_middleware';


        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {
            $count = $GLOBALS['test'][ self::run_times ];
            $count ++;
            $GLOBALS['test'][ self::run_times ] = $count;

            return $handler->handle($request);
        }

    }
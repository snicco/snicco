<?php


    declare(strict_types = 1);


    namespace Tests;

    use Psr\Http\Message\ResponseInterface;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;

   abstract class MiddlewareTestCase extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;


        /**
         * @var Delegate
         */
        protected $route_action;

        /**
         * @var TestRequest
         */
        protected $request;

        /**
         * @var ResponseFactory
         */
        protected $response_factory;

        /** @var UrlGenerator */
        protected $generator;

        /** @var RouteCollection */
        protected $routes;

        /** @return Middleware */
        abstract public function newMiddleware ();

        protected function runMiddleware(Request $request = null, Delegate $delegate = null ) : ResponseInterface
        {

            $m = $this->newMiddleware();
            $m->setResponseFactory($this->response_factory);

            $r = $request ?? $this->request;
            $d = $delegate ?? $this->route_action;

            return $m->handle($r, $d);

        }

        protected function newSession(int $lifetime = 10) : Session
        {
            return new Session(new ArraySessionDriver($lifetime));
        }

        protected function setUp() : void
        {

            parent::setUp();
            $this->response_factory = $this->createResponseFactory();
            $this->request = TestRequest::from('GET', '/foo');


        }


    }
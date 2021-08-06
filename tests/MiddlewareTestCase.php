<?php


    declare(strict_types = 1);


    namespace Tests;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;
    use Snicco\Routing\RouteCollection;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Session\Drivers\ArraySessionDriver;
    use Snicco\Session\Session;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;

    abstract class MiddlewareTestCase extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;

        protected Delegate $route_action;
        protected TestRequest $request;
        protected ResponseFactory $response_factory;
        protected UrlGenerator $generator;
        protected RouteCollection $routes;

        abstract public function newMiddleware () : Middleware;

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
<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\unit\UnitTest;
    use Tests\unit\View\MethodField;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Authenticate;
    use WPEmerge\Middleware\Authorize;
    use WPEmerge\Middleware\Core\MethodOverride;

    class MethodOverrideTest extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         * @var Authenticate
         */
        private $middleware;

        /**
         * @var Delegate
         */
        private $route_action;

        /**
         * @var TestRequest
         */
        private $request;

        /**
         * @var ResponseFactory
         */
        private $response;

        /**
         * @var MethodField
         */
        private $method_field;

        protected function beforeTestRun()
        {

            $response = $this->createResponseFactory();
            $this->route_action = new Delegate(function (Request $request) use ($response) {

                return $response->html($request->getMethod());

            });

            $this->method_field = new MethodField(TEST_APP_KEY);

        }

        private function newMiddleware() : MethodOverride
        {

            return new MethodOverride($this->method_field, $this->createContainer());

        }

        /** @test */
        public function the_method_can_be_overwritten_for_post_requests()
        {

            $request = TestRequest::from('POST', '/foo')->withParsedBody([
                $this->method_field->key() => 'PUT',
            ]);

            $response = $this->newMiddleware()->handle($request, $this->route_action);

            $this->assertOutput('PUT', $response);

        }

        /** @test */
        public function the_method_cant_be_overwritten_for_anything_but_post_requests()
        {

            $request = TestRequest::from('GET', '/foo')->withParsedBody([
                $this->method_field->key() => 'PUT',
            ]);
            $response = $this->newMiddleware()->handle($request, $this->route_action);
            $this->assertOutput('GET', $response);

            $request = TestRequest::from('PATCH', '/foo')->withParsedBody([
                $this->method_field->key() => 'PUT',
            ]);
            $response = $this->newMiddleware()->handle($request, $this->route_action);
            $this->assertOutput('PATCH', $response);

        }


    }
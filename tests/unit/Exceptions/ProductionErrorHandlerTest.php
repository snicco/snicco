<?php


    declare(strict_types = 1);


    namespace Tests\unit\Exceptions;

    use Contracts\ContainerAdapter;
    use Exception;
    use Respect\Validation\Validator as v;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\HeaderStack;
    use Tests\UnitTest;
    use Tests\stubs\TestException;
    use Tests\stubs\TestRequest;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\ExceptionHandling\Exceptions\HttpException;
    use BetterWP\Session\Drivers\ArraySessionDriver;
    use BetterWP\Session\Session;
    use BetterWP\Session\StatefulRedirector;
    use BetterWP\Support\WP;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Events\UnrecoverableExceptionHandled;
    use BetterWP\ExceptionHandling\ProductionErrorHandler;
    use BetterWP\Factories\ErrorHandlerFactory;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Validation\Exceptions\ValidationException;
    use BetterWP\Validation\Validator;

    class ProductionErrorHandlerTest extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;
        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /**
         * @var Request
         */
        private $request;

        protected function beforeTestRun()
        {

            ApplicationEvent::make($this->container = $this->createContainer());
            ApplicationEvent::fake();
            $this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
            $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
            WP::setFacadeContainer($this->createContainer());
            $this->request = TestRequest::from('GET', 'foo');

        }

        protected function beforeTearDown()
        {

            WP::reset();
            ApplicationEvent::setInstance(null);
            \Mockery::close();
            HeaderStack::reset();

        }

        /** @test */
        public function inside_the_routing_flow_the_exceptions_get_transformed_into_response_objects()
        {


            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new TestException('Sensitive Info'), $this->request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertOutput('VIEW:500,CONTEXT:[status_code=>500,message=>Internal Server Error]', $response);
            $this->assertStatusCode(500, $response);
            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);


        }

        /** @test */
        public function outside_the_routing_flow_exceptions_will_lead_to_script_termination()
        {

            $handler = $this->newErrorHandler();

            $this->container->instance(Request::class, $this->request);

            $handler->handleException(new TestException('Sensitive Info'));

            ApplicationEvent::assertDispatched(UnrecoverableExceptionHandled::class);
            $this->expectOutputString('VIEW:500,CONTEXT:[status_code=>500,message=>Internal Server Error]');

        }

        /** @test */
        public function the_response_will_be_sent_as_json_if_the_request_expects_json()
        {

            $handler = $this->newErrorHandler();

            $request = $this->request->withAddedHeader('Accept', 'application/json');

            $response = $handler->transformToResponse(new TestException('Sensitive Info'), $request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('application/json', $response);
            $this->assertSame(['message' =>'Internal Server Error'], json_decode($response->getBody()->__toString(), true ));
            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }

        /** @test */
        public function outside_the_routing_flow_responses_are_send_as_json_if_expected_by_the_request()
        {

            $handler = $this->newErrorHandler();

            $this->container->instance(Request::class, $this->request->withAddedHeader('Accept', 'application/json'));

            $handler->handleException(new TestException('Sensitive Info'));

            ApplicationEvent::assertDispatched(UnrecoverableExceptionHandled::class);
            HeaderStack::assertHasStatusCode(500);
            HeaderStack::assertHas('Content-Type', 'application/json');
            $this->expectOutputString(json_encode(['message' =>'Internal Server Error']));

        }

        /** @test */
        public function an_unspecified_exception_gets_converted_into_a_500_internal_server_error()
        {

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new TestException('Sensitive Info'), $this->request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('VIEW:500,CONTEXT:[status_code=>500,message=>Internal Server Error]', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }

        /** @test */
        public function an_exception_can_have_custom_rendering_logic()
        {

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new RenderableException(), $this->request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('Foo', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }

        /** @test */
        public function renderable_exceptions_MUST_return_a_response_object()
        {

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new WrongReturnTypeException(), $this->request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);

            // We rethrow the exception.
            $this->assertOutput('VIEW:500,CONTEXT:[status_code=>500,message=>Internal Server Error]', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }

        /** @test */
        public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance()
        {


            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new ExceptionWithDependencyInjection(), $this->request->withAttribute('foo', 'bar'));

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(403, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('bar', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }

        /** @test */
        public function a_custom_error_handler_can_replace_the_default_response_object()
        {

            $this->container->instance(ProductionErrorHandler::class, CustomErrorHandler::class);

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse(new Exception('Sensitive Data nobody should read'), $this->request);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('VIEW:500,CONTEXT:[status_code=>500,message=>Custom Error Message]', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

        }



        private function newErrorHandler() : ProductionErrorHandler
        {

            return ErrorHandlerFactory::make($this->container, false);

        }


    }


    class RenderableException extends Exception
    {

        public function render(ResponseFactory $factory)
        {

            return $factory->html('Foo')->withStatus(500);

        }

    }


    class WrongReturnTypeException extends Exception
    {

        public function render()
        {

            return 'foo';

        }

    }


    class ExceptionWithDependencyInjection extends Exception
    {

        public function render(Request $request, ResponseFactory $response_factory)
        {

            return $response_factory
                ->html($request->getAttribute('foo'))
                ->withStatus(403);

        }

    }


    class CustomErrorHandler extends ProductionErrorHandler
    {


        protected function toHttpException(\Throwable $e, Request $request) : HttpException
        {

            return new HttpException(500, 'Custom Error Message');

        }


    }


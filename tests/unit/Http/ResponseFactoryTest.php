<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestView;
    use Tests\UnitTest;
    use Snicco\Http\Psr7\Response;
    use Snicco\Http\Redirector;
    use Snicco\Http\ResponseFactory;
    use Snicco\Http\Responses\InvalidResponse;
    use Snicco\Http\Responses\NullResponse;
    use Snicco\Http\Responses\RedirectResponse;
    use Snicco\Http\Responses\WpQueryFilteredResponse;

    class ResponseFactoryTest extends UnitTest
    {

        use AssertsResponse;
        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         * @var ResponseFactory
         */
        private $factory;

        protected function setUp() : void
        {

            parent::setUp();

            $this->factory = $this->createResponseFactory();

        }

        public function testMake()
        {

            $response = $this->factory->make(204, 'Hello');

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(204, $response);
            $this->assertSame('Hello', $response->getReasonPhrase());

        }

        public function testView()
        {

            $response = $this->factory->view('test_view', ['foo' => 'bar'], 205, ['header1' => 'foo']);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(205, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('VIEW:test_view,CONTEXT:[foo=>bar]', $response);
            $this->assertHeader('header1', 'foo', $response);

        }

        public function testJson()
        {

            $response = $this->factory->json(['foo' => 'bar'], 401);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(401, $response);
            $this->assertContentType('application/json', $response);
            $this->assertOutput(json_encode(['foo' => 'bar']), $response);

        }

        /** @test */
        public function testNull () {

            $response = $this->factory->null();

            $this->assertInstanceOf(NullResponse::class, $response);

        }

        /** @test */
        public function testQueryFiltered () {

            $response = $this->factory->queryFiltered();

            $this->assertInstanceOf(NullResponse::class, $response);
            $this->assertInstanceOf(WpQueryFilteredResponse::class, $response);

        }

        /** @test */
        public function testToResponse_already_response () {

            $response = $this->factory->make();
            $result = $this->factory->toResponse($response);
            $this->assertSame($result, $response);



        }

        /** @test */
        public function testToResponse_psr7_response () {


            $response = $this->psrResponseFactory()->createResponse();
            $result = $this->factory->toResponse($response);
            $this->assertNotSame($result, $response);
            $this->assertInstanceOf(Response::class, $result);


        }

        /** @test */
        public function testToResponse_is_string () {

            $result = $this->factory->toResponse('foo');
            $this->assertInstanceOf(Response::class, $result);

            $this->assertContentType('text/html', $result);
            $this->assertOutput('foo', $result );

        }

        /** @test */
        public function testToResponse_is_array () {

            $input = ['foo' => 'bar', 'bar' => 'baz'];

            $response = $this->factory->toResponse($input);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertContentType('application/json', $response);
            $this->assertOutput(json_encode($input), $response);


        }

        /** @test */
        public function testToResponse_is_responseable () {

            $view = new TestView('view');

            $response = $this->factory->toResponse($view);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('VIEW:view,CONTEXT:[]', $response);

        }

        /** @test */
        public function testToResponse_is_invalid () {

            $response = $this->factory->toResponse(1);

            $this->assertInstanceOf(InvalidResponse::class, $response);

        }

        /** @test */
        public function testRedirect_return_redirector () {

            $this->assertInstanceOf(Redirector::class, $this->factory->redirect());

        }

        /** @test */
        public function testInvalidResponse () {

            $response = $this->factory->invalidResponse();

            $this->assertInstanceOf(InvalidResponse::class, $response);
            $this->assertStatusCode(500, $response);


        }

        /** @test */
        public function testNoContent () {

            $response = $this->factory->noContent();

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(204, $response);

        }



    }
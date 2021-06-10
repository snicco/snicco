<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Http\Psr7\Request;

    class InteractsWithInputTest extends UnitTest
    {

        /**
         * @var Request
         */
        private $request;

        protected function setUp() : void
        {

            parent::setUp();

            $this->request = TestRequest::from('GET', 'foo');
        }

        // /** @test */
        public function json () {

            $this->request->getBody()->write('{"bar":"foo"}');
            $request = $this->request;

        }

    }
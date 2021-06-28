<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Testing\TestCase;

    class RouteTest extends TestCase
    {

        public function createApplication() : Application
        {
            $app = TestApp::make(FIXTURES_DIR);
            $f = new Psr17Factory();
            $app->setServerRequestFactory($f);
            $app->setStreamFactory($f);
            $app->setUploadedFileFactory($f);
            $app->setUriFactory($f);
            return $app;
        }

        /** @test */
        public function testGet () {

            $response = $this->get('/foo');

        }

    }
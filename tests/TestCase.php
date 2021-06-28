<?php


    declare(strict_types = 1);


    namespace Tests;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Testing\TestCase as BaseTestCase;

    class TestCase extends BaseTestCase
    {

        protected function setUp() : void
        {

            parent::setUp();
            $GLOBALS['test'] = [];

        }

        protected function tearDown() : void
        {
            $GLOBALS['test'] = [];
            parent::tearDown();
        }

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

    }
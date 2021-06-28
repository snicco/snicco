<?php


    declare(strict_types = 1);


    namespace Tests;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Support\Arr;
    use WPEmerge\Testing\TestCase as BaseTestCase;
    use WPEmerge\Testing\TestResponse;

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

        protected function sendResponse () :TestResponse {

            $r =  $this->app->resolve(ResponseEmitter::class)->response;

            if ( ! $r instanceof TestResponse ) {
                $this->fail('No response was sent.');
            }

            return $r;

        }

        protected function withAddedProvider($provider) {
            $provider = Arr::wrap($provider);

            foreach ($provider as $p) {

                $this->withAddedConfig(['app.providers' => [$p]]);

            }

            return $this;

        }

        protected function withoutHooks() {
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];

            return $this;

        }

    }
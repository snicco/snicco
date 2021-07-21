<?php


    declare(strict_types = 1);


    namespace Tests;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use PHPUnit\Framework\Assert;
    use Tests\stubs\TestApp;
    use Snicco\Application\Application;
    use Snicco\Contracts\ViewInterface;
    use Snicco\Http\ResponseEmitter;
    use Snicco\Support\Arr;
    use Snicco\Testing\TestCase as BaseTestCase;
    use Snicco\Testing\TestResponse;

    class TestCase extends BaseTestCase
    {

        /**
         * @var array
         */
        protected $mail_data;

        protected function setUp() : void
        {

            parent::setUp();
            $GLOBALS['test'] = [];

            add_filter('pre_wp_mail', [$this, 'catchWpMail'], 10, 2);

        }

        protected function tearDown() : void
        {
            $GLOBALS['test'] = [];
            TestApp::setApplication(null);
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
            $app->setResponseFactory($f);

            return $app;

        }

        public function catchWpMail($null, array $wp_mail_input) : bool
        {

            $this->mail_data[] = $wp_mail_input;

            return true;

        }

        protected function sentResponse () :TestResponse {

            $r =  $this->app->resolve(ResponseEmitter::class)->response;

            if ( ! $r instanceof TestResponse ) {
                $this->fail('No response was sent.');
            }

            return $r;

        }

        protected function withAddedProvider($provider) : TestCase
        {
            $provider = Arr::wrap($provider);

            foreach ($provider as $p) {

                $this->withAddedConfig(['app.providers' => [$p]]);

            }

            return $this;

        }

        protected function withoutHooks() : TestCase
        {
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];

            return $this;

        }

        protected function assertNoResponse(){

            $this->assertNull($this->app->resolve(ResponseEmitter::class)->response);

        }

        protected function assertViewContent(string $expected,  $actual) {

            $actual = ($actual instanceof ViewInterface) ? $actual->toString() :$actual;

            $actual = preg_replace( "/\r|\n|\s{2,}/", "", $actual );

            Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');

        }

    }
<?php


    declare(strict_types = 1);


    namespace Tests\integration\Application;

    use Codeception\TestCase\WPTestCase;
    use Tests\helpers\CreateContainer;
    use Tests\stubs\TestApp;

    class ApplicationConfigTest extends WPTestCase
    {

        use CreateContainer;

        public function testConfigFilesGetLoaded () {

            $app = TestApp::make($this->createContainer());

            $app->runningUnitTest();

            TestApp::boot(TESTS_DIR . DS . 'fixtures' );

            $config = TestApp::config();

            $this->assertTrue($config->has('app'));
            $this->assertTrue($config->has('foo'));
            $this->assertTrue($config->has('bar'));

            $this->assertSame('buu', $config->get('foo.bar'));
            $this->assertSame('biz', $config->get('bar.baz'));

        }


    }
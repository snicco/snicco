<?php


    declare(strict_types = 1);


    namespace Tests;

    use Codeception\TestCase\WPTestCase;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;

    class IntegrationTest extends WPTestCase
    {

        public function newTestApp ( array $config = [] ) : Application
        {

            WP::reset();

            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
            TestApp::setApplication(null);
            ApplicationEvent::setInstance(null);

            $app = TestApp::make();
            $app->boot($config);

            return $app;
        }

        protected function tearDown() : void
        {
            parent::tearDown();
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
        }

    }
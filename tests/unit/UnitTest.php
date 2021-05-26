<?php


    declare(strict_types = 1);


    namespace Tests\unit;

    use BetterWpHooks\Testing\BetterWpHooksTestCase;
    use Tests\helpers\CreateContainer;
    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteMatcher;

    class UnitTest extends BetterWpHooksTestCase
    {

        use CreatePsr17Factories;
        use CreateContainer;
        use CreateRouteMatcher;

        protected function setUp() : void
        {

            parent::setUp();

            $this->resetGlobalState();
            $this->createDefaultWpApiMocks();
            $this->beforeTestRun();

        }

        protected function tearDown() : void
        {

            $this->beforeTearDown();
            $this->resetGlobalState();
            $this->tearDownWp();
            parent::tearDown();
        }

        protected function resetGlobalState()
        {

            $GLOBALS['test'] = [];
        }

        protected function createDefaultWpApiMocks()
        {
            //
        }

        protected function beforeTestRun()
        {
            //
        }

        protected function beforeTearDown()
        {

            //

        }

    }
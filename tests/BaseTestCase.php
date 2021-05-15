<?php


    declare(strict_types = 1);


    namespace Tests;

    use PHPUnit\Framework\TestCase;

    class BaseTestCase extends TestCase
    {

        use CreatePsr17Factories;
        use CreateContainer;

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
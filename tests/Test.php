<?php


	declare( strict_types = 1 );


	namespace Tests;

	use PHPUnit\Framework\TestCase as PhpUnitTest;
	use Tests\stubs\TestApp;
	use WPEmerge\Application\ApplicationEvent;
	use WpFacade\WpFacade;

	class Test extends PhpUnitTest {

		protected function setUp() : void {

			parent::setUp();

			$GLOBALS['test'] = [];


			if ( method_exists( $this, 'afterSetup' ) ) {

				$this->afterSetup();

			}

			if ( method_exists( $this, 'setWpApiMocks' ) ) {

				$this->setWpApiMocks();

			}

		}

		protected function tearDown() : void {

			if ( method_exists( $this, 'closeMockery' ) ) {

				$this->closeMockery();

			}

			if ( method_exists( $this, 'beforeTearDown' ) ) {

				$this->beforeTearDown();

			}

			parent::tearDown();

			$GLOBALS['test'] = [];
			WpFacade::clearResolvedInstances();
			TestApp::setApplication(null );
			ApplicationEvent::setInstance(null);

		}


	}
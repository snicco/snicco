<?php


	namespace Tests\integration\View;

	use Codeception\TestCase\WPTestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestApp;

	class ViewServiceIntegrationTest extends WPTestCase {


		/**
		 * @var \WPEmerge\View\ViewService
		 */
		private $view_service;


		protected function setUp() : void {

			parent::setUp();

			$container = new BaseContainerAdapter();

			TestApp::make($container)->bootstrap(TEST_CONFIG);

			 $this->view_service = TestApp::resolve(WPEMERGE_VIEW_SERVICE_KEY);

		}




	}
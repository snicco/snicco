<?php


	namespace Tests\integration\HttpKernel;

	use PHPUnit\Framework\TestCase;

	class HttpKernelWpHookInteractionsTest extends TestCase {


		use SetUpKernel;


		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response_for_admin_requests() {

			$this->kernel->sendBodyDeferred();

			$this->assertNull($this->response_service->body_response);


		}



	}
<?php


	namespace Tests\unit\Traits;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Traits\CompilesMiddleware;
	use WPEmerge\Traits\HoldsMiddlewareDefinitions;

	class CompilesMiddlewareTest extends TestCase {


		/** @test */
		public function middleware_gets_correctly_split_into_arguments () {



		}

		/** @test */
		public function there_can_be_no_middleware_duplicates () {



		}


	}



	class MiddlewareImplementation {

		use CompilesMiddleware;
		use HoldsMiddlewareDefinitions;

	}
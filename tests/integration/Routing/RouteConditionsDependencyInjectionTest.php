<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Conditions\ConditionWithDependency;


	class RouteConditionsDependencyInjectionTest extends TestCase {

		use SetUpRouter;


		/** @test */
		public function a_condition_gets_dependencies_injected_after_the_passed_arguments() {

			$this->router->get( '/foo', function ( ) {

				return 'foo';

			})->where(ConditionWithDependency::class, true);

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

		}

	}


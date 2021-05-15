<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

	use Mockery;
    use Tests\BaseTestCase;
    use Tests\traits\SetUpRouter;
    use Tests\stubs\Conditions\ConditionWithDependency;
    use WPEmerge\Facade\WP;

    class RouteConditionsDependencyInjectionTest extends BaseTestCase {

		use SetUpRouter;

        protected function beforeTestRun()
        {
            $this->newRouter( $c = $this->createContainer() );
            WP::setFacadeContainer($c);
        }

        protected function beforeTearDown()
        {

            Mockery::close();
            WP::clearResolvedInstances();
            WP::setFacadeContainer(null);

        }

		/** @test */
		public function a_condition_gets_dependencies_injected_after_the_passed_arguments() {

			$this->router->get( '/foo', function ( ) {

				return 'foo';

			})->where(ConditionWithDependency::class, true);

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foo', $this->router->runRoute( $request ) );

		}

	}


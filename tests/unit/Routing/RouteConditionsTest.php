<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

	use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\fixtures\Conditions\TrueCondition;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\unit\UnitTest;
    use Tests\fixtures\Conditions\FalseCondition;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Router;

    class RouteConditionsTest extends UnitTest {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }


        /** @test */
		public function custom_conditions_can_be_added_as_strings() {

		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( 'false' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    });

		    });

		    $request = $this->webRequest('GET', 'foo');
		    $this->runAndAssertEmptyOutput($request);

		}

		/** @test */
		public function custom_conditions_can_be_added_as_objects() {

		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( new FalseCondition() )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });

            $request = $this->webRequest('GET', 'foo');
            $this->runAndAssertEmptyOutput($request);


		}

		/** @test */
		public function custom_conditions_can_be_added_before_the_http_verb() {

		    $this->createRoutes(function () {

                $this->router
                    ->where( new TrueCondition() )
                    ->get( '/foo' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->where( 'false' )
                    ->post( '/bar' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    });

		    });

            $this->runAndAssertOutput('foo', $this->webRequest('GET', 'foo'));

            $this->runAndAssertEmptyOutput( $this->webRequest('GET', 'bar') );


		}

		/** @test */
		public function a_condition_stack_can_be_added_before_the_http_verb() {


		    $this->createRoutes(function () {

                $this->router
                    ->where( function ( $foo ) {

                        $GLOBALS['test']['cond1'] = $foo;

                        return $foo === 'foo';

                    }, 'foo' )
                    ->where( function ( $bar ) {

                        $GLOBALS['test']['cond2'] = $bar;

                        return $bar === 'bar';

                    }, 'bar' )
                    ->get( '/baz' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });


		    $this->runAndAssertOutput('foo', $this->webRequest('GET', '/baz'));
			$this->assertSame( 'bar', $GLOBALS['test']['cond2'] );
			$this->assertSame( 'foo', $GLOBALS['test']['cond1'] ?? null, 'First condition did not execute' );


		}

		/** @test */
		public function a_closure_can_be_a_condition() {


		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( function () {

                        return true;

                    } )
                    ->where(
                        function ( $foo, $bar ) {

                            return $foo === 'foo' && $bar === 'bar';

                        },
                        'foo',
                        'bar'
                    )
                    ->handle(
                        function ( $request, $foo, $bar ) {

                            return $foo . $bar;

                        }
                    );

                $this->router
                    ->post( '/will-fail' )
                    ->where(
                        function ( $foo, $bar ) {

                            return $foo === 'foo' && $bar === 'bar';

                        },
                        'foo',
                        'baz'
                    )
                    ->handle(
                        function ( $request, $foo, $bar ) {

                            return $foo . $bar;

                        }
                    );

                $this->router
                    ->where(
                        function ( $foo, $bar ) {

                            return $foo === 'foo' && $bar === 'bar';

                        },
                        'foo',
                        'bar'
                    )
                    ->put( '/foo-before' )
                    ->handle(
                        function ( $request, $foo, $bar ) {

                            return $foo . $bar;

                        }
                    );

		    });


		    $this->runAndAssertOutput('foobar', $this->webRequest('GET', '/foo'));
		    $this->runAndAssertOutput('foobar', $this->webRequest('PUT', '/foo-before'));

		    $this->runAndAssertEmptyOutput($this->webRequest('POST', '/will-fail'));



		}

		/** @test */
		public function multiple_conditions_can_be_combined_and_all_conditions_have_to_pass() {

		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( 'true' )
                    ->where( 'false' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });


		    $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo'));


		}

		/** @test */
		public function a_condition_can_be_negated() {

            $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( '!false' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->post( '/bar' )
                    ->where( 'negate', 'false' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->put( '/baz' )
                    ->where( 'negate', function ( $foo ) {

                        return $foo !== 'foo';

                    }, 'foo' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

            });



            $this->runAndAssertOutput('foo', $this->webRequest('GET', '/foo'));
            $this->runAndAssertOutput('foo', $this->webRequest('POST', '/bar'));
            $this->runAndAssertOutput('foo', $this->webRequest('PUT', '/baz'));




		}

		/** @test */
		public function a_condition_can_be_negated_while_passing_arguments() {

		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( 'maybe', true )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->post( '/bar' )
                    ->where( 'maybe', false )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->put( '/baz' )
                    ->where( '!maybe', false )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->delete( '/baz' )
                    ->where( '!maybe', false )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

                $this->router
                    ->patch( '/foobar' )
                    ->where( '!maybe', 'foobar' )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });




			$this->runAndAssertOutput( 'foo', $this->webRequest( 'GET', '/foo' )  );
			$this->runAndAssertOutput( 'foo', $this->webRequest( 'PUT', '/baz' )  );
			$this->runAndAssertOutput( 'foo', $this->webRequest( 'DELETE', '/baz' )  );

			$this->runAndAssertEmptyOutput( $this->webRequest( 'PATCH', '/foobar' )  );
            $this->runAndAssertEmptyOutput( $this->webRequest( 'POST', '/bar' )  );


		}

		/** @test */
		public function matching_url_conditions_will_fail_if_custom_conditions_are_not_met() {


		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( 'maybe', false )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });



            $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo'));
			$this->assertTrue( $GLOBALS['test']['maybe_condition_run'] );

		}

		/** @test */
		public function a_condition_object_can_be_negated() {

		    $this->createRoutes(function () {


                $this->router
                    ->get( '/foo' )
                    ->where( 'negate', new FalseCondition() )
                    ->handle( function ( Request $request ) {

                        return 'foo';

                    } );

		    });



            $this->runAndAssertOutput('foo', $this->webRequest('GET', '/foo'));



		}

		/** @test */
		public function failure_of_only_one_condition_leads_to_immediate_rejection_of_the_route() {

		    $this->createRoutes(function () {

                $this->router
                    ->get( '/foo' )
                    ->where( 'false' )
                    ->where( function () {

                        $this->fail( 'This condition should not have been called.' );

                    } )
                    ->handle( function ( Request $request ) {

                        $this->fail('This should never be called.');

                    } );

		    });




			$this->runAndAssertEmptyOutput($this->webRequest( 'GET', '/foo' )  );

		}

		/** @test */
		public function conditions_can_be_resolved_using_the_service_container() {


		    $this->createRoutes(function () {

                $this->router
                    ->where( 'dependency_condition', true )
                    ->get( 'foo', function () {

                        return 'foo';

                    } );


                $this->router
                    ->where( 'dependency_condition', false )
                    ->post( 'foo', function () {

                        return 'foo';

                    } );


            });


		    $this->runAndAssertOutput('foo', $this->webRequest('GET', '/foo'));
		    $this->runAndAssertEmptyOutput( $this->webRequest('POST', '/foo'));




		}

		/** @test */
		public function global_functions_can_be_used_as_custom_conditions() {


		    $this->createRoutes(function () {

                $this->router->where( 'is_string', 'foo' )->get( 'foo', function () {

                    return 'foo';

                } );


                $this->router
                    ->where( 'is_string', 1 )
                    ->post( 'foo', function () {

                        return 'foo';

                    } );


                $this->router
                    ->where( '!is_string', 1 )
                    ->put( 'foo', function () {

                        return 'foo';

                    } );

		    });



            $this->runAndAssertOutput('foo', $this->webRequest('GET', '/foo'));


            $this->runAndAssertOutput('foo', $this->webRequest('PUT', '/foo'));


            $this->runAndAssertEmptyOutput($this->webRequest('POST', '/foo'));







		}


	}
<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\UnitTest;
    use Tests\traits\SetUpRouter;
    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Str;

    class ReverseRoutingTest extends UnitTest
    {

        use SetUpRouter;

        /** @var UrlGenerator */
        private $url_generator;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);

            $this->url_generator = new UrlGenerator(
                new FastRouteUrlGenerator($this->routes),
            );

        }

        protected function beforeTearDown()
        {

            Mockery::close();
            WP::clearResolvedInstances();
            WP::setFacadeContainer(null);

        }


        /** @test */
        public function a_route_can_be_named()
        {

            $this->router->get('foo')->name('foo_route');
            $this->router->name('bar_route')->get('bar');

            $url = $this->url_generator->toRoute('foo_route');
            $this->seeFullUrl('/foo', $url);

            $url = $this->url_generator->toRoute('bar_route');
            $this->seeFullUrl('/bar', $url);

        }

        /** @test */
        public function a_relative_url_can_be_created () {

            $this->router->get('foo')->name('foo_route');
            $url = $this->url_generator->toRoute('foo_route', [] , false);
            $this->assertSame('/foo', $url);


        }

        /** @test */
        public function route_names_are_merged_on_multiple_levels()
        {

            $this->router
                ->name('foo')
                ->group(function () {

                    $this->router->name('bar')->group(function () {

                        $this->router->get('baz')->name('baz');

                    });

                    $this->router->get('biz')->name('biz');


                });

            $this->seeFullUrl('/baz', $this->url_generator->toRoute('foo.bar.baz'));
            $this->seeFullUrl('/biz', $this->url_generator->toRoute('foo.biz'));

            $this->expectExceptionMessage('no named route');

            $this->seeFullUrl('/baz', $this->url_generator->toRoute('foo.bar.biz'));


        }

        /** @test */
        public function group_names_get_applied_to_child_routes()
        {

            $this->router
                ->name('foo')
                ->group(function () {

                    $this->router->get('bar')->name('bar');

                    $this->router->get('baz')->name('baz');

                    $this->router->name('biz')->get('biz');

                });

            $this->seeFullUrl('/bar', $this->url_generator->toRoute('foo.bar'));
            $this->seeFullUrl('/baz', $this->url_generator->toRoute('foo.baz'));
            $this->seeFullUrl('/biz', $this->url_generator->toRoute('foo.biz'));


        }

        /** @test */
        public function urls_for_routes_with_required_segments_can_be_generated()
        {

            $this->router->get('/foo/{required}')->name('foo');
            $url = $this->url_generator->toRoute('foo', ['required' => 'bar']);
            $this->seeFullUrl('/foo/bar', $url);

        }

        /** @test */
        public function urls_for_routes_with_optional_segments_can_be_generated()
        {

            $this->router->get('foo/{required}/{optional?}')->name('foo');
            $url = $this->url_generator->toRoute('foo', [
                'required' => 'bar',
                'optional' => 'baz',
            ]);
            $this->seeFullUrl('/foo/bar/baz', $url);

        }

        /** @test */
        public function optional_segments_can_be_left_blank()
        {

            $this->router->get('foo/{optional?}')->name('foo');
            $url = $this->url_generator->toRoute('foo');
            $this->seeFullUrl('/foo', $url);

            $this->router->get('bar/{required}/{optional?}')->name('bar');
            $url = $this->url_generator->toRoute('bar', ['required' => 'baz']);
            $this->seeFullUrl('/bar/baz', $url);


        }

        /** @test */
        public function optional_segments_can_be_created_after_fixed_segments()
        {

            $this->router->get('foo/{optional?}')->name('foo');
            $url = $this->url_generator->toRoute('foo', ['optional' => 'bar']);
            $this->seeFullUrl('/foo/bar', $url);

        }

        /** @test */
        public function multiple_optional_segments_can_be_created()
        {

            $this->router->get('foo/{opt1?}/{opt2?}/')->name('foo');

            $this->router->loadRoutes();

            $url = $this->url_generator->toRoute('foo', ['opt1' => 'bar', 'opt2' => 'baz']);
            $this->seeFullUrl('/foo/bar/baz', $url);

            $this->router->get('bar/{required}/{opt1?}/{opt2?}')->name('bar');
            $url = $this->url_generator->toRoute('bar', [
                'required' => 'biz',
                'opt1' => 'bar',
                'opt2' => 'baz',
            ]);
            $this->seeFullUrl('/bar/biz/bar/baz', $url);


        }

        /** @test */
        public function required_segments_can_be_created_with_regex_constraints()
        {

            $this->router->get('/foo/{required}')->name('foo')->and('required', '\w+');
            $url = $this->url_generator->toRoute('foo', ['required' => 'bar']);
            $this->seeFullUrl('/foo/bar', $url);

        }

        /** @test */
        public function optional_segments_can_be_created_with_regex()
        {

            $this->router->get('/foo/{optional?}')->name('foo')->and('optional', '\w+');
            $url = $this->url_generator->toRoute('foo', ['optional' => 'bar']);
            $this->seeFullUrl('/foo/bar', $url);

        }

        /** @test */
        public function required_and_optional_segments_can_be_created_with_regex()
        {

            $this->router->get('/foo/{required}/{optional?}')
                         ->name('foo')
                         ->and(['required', '\w+', 'optional', '\w+']);

            $url = $this->url_generator->toRoute('foo', ['required' => 'bar']);
            $this->seeFullUrl('/foo/bar', $url);

            $this->router->get('/bar/{required}/{optional?}')
                         ->name('bar')
                         ->and(['required' => '\w+', 'optional' => '\w+']);

            $url = $this->url_generator->toRoute('bar', [
                'required' => 'baz',
                'optional' => 'biz',
            ]);
            $this->seeFullUrl('/bar/baz/biz', $url);

            $this->router->get('/foo/{required}/{optional1?}/{optional2?}')
                         ->name('foobar')
                         ->and([
                             'required' => '\w+',
                             'optional1' => '\w+',
                             'optional2' => '\w+',
                         ]);

            $url = $this->url_generator->toRoute('foobar', [
                'required' => 'bar',
                'optional1' => 'baz',
                'optional2' => 'biz',
            ]);
            $this->seeFullUrl('/foo/bar/baz/biz', $url);


        }

        /** @test */
        public function missing_required_arguments_throw_an_exception()
        {

            $this->expectExceptionMessage('Required route segment: {required} missing');

            $this->router->get('foo/{required}')->name('foo');
            $url = $this->url_generator->toRoute('foo');


        }

        /** @test */
        public function an_exception_gets_thrown_if_the_passed_arguments_dont_satisfy_regex_constraints()
        {

            $this->expectExceptionMessage(
                'The provided value [#] is not valid for the route: [foo]');

            $this->router->get('/foo/{required}')
                         ->name('foo')
                         ->and(['required' => '\w+']);

            $this->url_generator->toRoute('foo', ['required' => '#']);

        }

        /** @test */
        public function custom_conditions_that_can_be_transformed_take_precedence_over_http_conditions()
        {


            $this->router->get('foo')->name('foo_route')->where(ConditionWithUrl::class);
            $url = $this->url_generator->toRoute('foo_route');
            $this->seeFullUrl('/foo/bar', $url);

        }


        /**
         *
         *
         *
         *
         * EDGE CASES
         *
         *
         *
         *
         *
         */

        /** @test */
        public function the_route_contains_segments_that_have_regex_using_curly_brackets_resulting_in_triple_curly_brackets_at_the_end_of_the_url()
        {

            $this->router
                ->get('/foo/{bar}')
                ->name('foo')
                ->and('bar', 'a{2,}');

            $url = $this->url_generator->toRoute('foo', ['bar' => 'aaa']);
            $this->seeFullUrl('/foo/aaa', $url);

            $url = $this->url_generator->toRoute('foo', ['bar' => 'aaaa']);
            $this->seeFullUrl('/foo/aaaa', $url);

            try {

                $this->url_generator->toRoute('foo', ['bar' => 'a']);
                $this->fail('Invalid constraint created a route.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [a] is not valid for the route',
                    $e->getMessage()
                );

            }

            try {

                $this->url_generator->toRoute('foo', ['bar' => 'bbbb']);
                $this->fail('Invalid constraint created a route.');
            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [bbbb] is not valid for the route',
                    $e->getMessage()
                );

            }


        }

        /** @test */
        public function the_route_contains_segments_that_have_regex_using_curly_brackets_and_square_brackets()
        {

            $this->router
                ->get('/foo/{bar}')
                ->name('foo')
                ->and('bar', 'a{2,}[calvin]');

            $url = $this->url_generator->toRoute('foo', ['bar' => 'aacalvin']);
            $this->seeFullUrl('/foo/aacalvin', $url);

            $this->expectExceptionMessage('The provided value [aajohn] is not valid for the route');

            $this->url_generator->toRoute('foo', ['bar' => 'aajohn']);

        }

        /** @test */
        public function problematic_regex_inside_required_and_optional_segments()
        {

            $this->router
                ->get('/teams/{team}/{player?}')
                ->name('teams')
                ->and([

                    'team' => 'm{1}.+united[xy]',
                    'player' => 'a{2,}[calvin]',

                ]);

            $url = $this->url_generator->toRoute('teams', [
                'team' => 'manchesterunitedx',
                'player' => 'aacalvin',
            ]);
            $this->seeFullUrl('/teams/manchesterunitedx/aacalvin', $url);

            // Fails because not starting with m.
            try {

                $this->url_generator->toRoute('teams', [
                    'team' => 'lanchesterunited',
                    'player' => 'aacalvin',
                ]);
                $this->fail('Invalid constraint created a route.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [lanchesterunited] is not valid for the route',
                    $e->getMessage()
                );

            }

            // Fails because not using united.
            try {

                $this->url_generator->toRoute('teams', [
                    'team' => 'manchestercityx',
                    'player' => 'aacalvin',
                ]);
                $this->fail('Invalid constraint created a route.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [manchestercityx] is not valid for the route',
                    $e->getMessage()
                );

            }

            // Fails because not using x or y at the end.
            try {

                $this->url_generator->toRoute('teams', [
                    'team' => 'manchesterunitedz',
                    'player' => 'aacalvin',
                ]);
                $this->fail('Invalid constraint created a route.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [manchesterunitedz] is not valid for the route',
                    $e->getMessage()
                );

            }

            // Fails because optional parameter is present but doesnt match regex, only one a
            try {

                $this->url_generator->toRoute('teams', [
                    'team' => 'manchesterunitedx',
                    'player' => 'acalvin',
                ]);
                $this->fail('Invalid constraint created a route.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString(
                    'The provided value [acalvin] is not valid for the route',
                    $e->getMessage()
                );

            }

        }


        private function seeFullUrl($route_path, $result)
        {

            $expected = rtrim(SITE_URL, '/').'/'.ltrim($route_path, '/');

            // Strip https, http
            $expected = Str::after($expected, '://');
            $result = Str::after($result, '://');

            $this->assertSame($expected, $result);

        }


    }


    class ConditionWithUrl implements UrlableInterface, ConditionInterface
    {

        public function toUrl(array $arguments = []) : string
        {

            return SITE_URL.'foo/bar';

        }

        public function isSatisfied(Request $request) : bool
        {

            return true;

        }

        public function getArguments(Request $request) : array
        {

            return [];

        }

    }
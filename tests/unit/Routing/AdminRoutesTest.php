<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use Tests\traits\CreateWpTestUrls;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\UrlGenerator;

    class AdminRoutesTest extends UnitTest
    {

        use TestHelpers;
        use CreateWpTestUrls;
        use CreateDefaultWpApiMocks;

        private $router;

        private $container;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);
            WP::shouldReceive('isAdmin')->andReturnTrue();

        }

        protected function beforeTearDown()
        {

            Mockery::close();
            ApplicationEvent::setInstance(null);
            WP::reset();

        }


        /** @test */
        public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin.php/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo'));
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function routes_to_different_admin_pages_dont_match()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin.php/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('bar'));
            $this->runAndAssertOutput('', $request);


        }

        /** @test */
        public function the_admin_preset_works_with_nested_route_groups()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->group(['name' => 'foo_group'], function () {

                        $this->router->get('admin.php/foo', function (Request $request, string $page) {

                            return $page;

                        });

                    });


                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function two_different_admin_routes_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin.php/foo', function (Request $request, string $page) {

                        return $page;

                    });

                    $this->router->get('admin.php/bar', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo'));
            $this->runAndAssertOutput('foo', $request);

            $request = new IncomingAdminRequest($this->adminRequestTo('bar'));
            $this->runAndAssertOutput('bar', $request);

            $request = new IncomingAdminRequest($this->adminRequestTo('baz'));
            $this->runAndAssertOutput('', $request);


        }

        /** @test */
        public function admin_routes_can_match_different_inbuilt_wp_subpages()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('users.php/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'users.php'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('users.php/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'admin.php'));
            $this->runAndAssertOutput('', $request);

        }

        /** @test */
        public function reverse_routing_works_with_admin_routes()
        {

            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin', 'name' => 'admin'], function () {

                    $this->router->name('foo')
                                 ->get('admin.php/foo', function (Request $request, string $page) {

                                     return $page;

                                 });

                });

            });

            $url = (new UrlGenerator(new FastRouteUrlGenerator($this->routes)))->toRoute('admin.foo');
            $this->assertSame($this->adminUrlTo('foo'), $url);


        }

        /** @test */
        public function admin_routes_strip_a_possible_trailing_slash_in_the_route_definition()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin.php/foo/', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo'));
            $this->runAndAssertOutput('foo', $request);

        }

        /**
         *
         *
         *
         *
         * ALIASING ADMIN ROUTES
         *
         *
         *
         *
         *
         */

        /** @test */
        public function admin_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function options_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('options/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'options-general.php'));
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function tools_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('tools/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'tools.php'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function users_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('users/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'users.php'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function plugins_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('plugins/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'plugins.php'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function themes_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('themes/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'themes.php'));
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function comments_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('comments/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'edit-comments.php'));
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function upload_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('upload/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'upload.php'));
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function edit_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('posts/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'edit.php'));
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function index_php_routes_can_be_aliases_for_convenience()
        {

            $this->createRoutes(function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('dashboard/foo', function (Request $request, string $page) {

                        return $page;

                    });

                });

            });

            $request = new IncomingAdminRequest($this->adminRequestTo('foo', 'GET', 'index.php'));
            $this->runAndAssertOutput('foo', $request);


        }



    }
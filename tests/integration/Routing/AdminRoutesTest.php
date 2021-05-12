<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\RequestTesting;
    use Tests\TestCase;
    use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Facade\WP;

    class AdminRoutesTest extends TestCase
    {

        use SetUpRouter;
        use RequestTesting;

        protected function afterSetUp()
        {

            WP::shouldReceive('isAdmin')->andReturnTrue();

        }

        /** @test */
        public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('admin.php/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function routes_to_different_admin_pages_dont_match()
        {


            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('foo', function () {

                    return 'foo';
                });

            });

            $request = $this->adminRequestTo('bar');

            $this->assertSame(null, $this->router->runRoute($request));


        }

        /** @test */
        public function the_admin_preset_works_with_nested_route_groups()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->group(['name' => 'foo_group'], function () {

                    $this->router->get('admin.php/foo', function (RequestInterface $request, string $page) {

                        return $page;
                    });

                });


            });

            $request = $this->adminRequestTo('foo');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function two_different_admin_routes_can_be_created()
        {


            $routes = function () {

                $this->router->group(['prefix' => 'wp-admin'], function () {

                    $this->router->get('admin.php/foo', function (RequestInterface $request, string $page) {

                        return $page;

                    });

                    $this->router->get('admin.php/bar', function (RequestInterface $request, string $page) {

                        return $page;

                    });

                });

            };

            $this->newRouterWith($routes);
            $request = $this->adminRequestTo('foo');
            $this->assertSame('foo', $this->router->runRoute($request));

            $this->newRouterWith($routes);
            $request = $this->adminRequestTo('bar');
            $this->assertSame('bar', $this->router->runRoute($request));

            $this->newRouterWith($routes);
            $request = $this->adminRequestTo('baz', 'POST');
            $this->assertSame(null, $this->router->runRoute($request));


        }

        /** @test */
        public function admin_routes_can_match_different_inbuilt_wp_subpages()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('users.php/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'users.php');

            $this->assertSame('foo', $this->router->runRoute($request));
        }

        /** @test */
        public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('users.php/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'admin.php');

            $this->assertSame(null, $this->router->runRoute($request));

        }

        /** @test */
        public function reverse_routing_works_with_admin_routes()
        {

            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });

            $this->router->group(['prefix' => 'wp-admin', 'name' => 'admin'], function () {

                $this->router->get('admin.php/foo', function (RequestInterface $request, string $page) {

                    return $page;

                })->name('foo');

            });

            $url = $this->router->getRouteUrl('admin.foo');

            $this->assertSame($this->adminUrlTo('foo'), $url);


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

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('admin/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function options_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('options/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'options-general.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function tools_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('tools/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'tools.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function users_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('users/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'users.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function plugins_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('plugins/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'plugins.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function themes_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('themes/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'themes.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function comments_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('comments/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'edit-comments.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function upload_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('upload/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'upload.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function edit_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('posts/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'edit.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }

        /** @test */
        public function index_php_routes_can_be_aliases_for_convenience()
        {

            $this->router->group(['prefix' => 'wp-admin'], function () {

                $this->router->get('dashboard/foo', function (RequestInterface $request, string $page) {

                    return $page;

                });

            });

            $request = $this->adminRequestTo('foo', 'GET', 'index.php');

            $this->assertSame('foo', $this->router->runRoute($request));

        }


    }
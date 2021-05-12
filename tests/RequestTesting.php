<?php


    declare(strict_types = 1);


    namespace Tests;

    use WPEmerge\Support\Url;

    trait RequestTesting
    {

        private function adminUrlTo(string $menu_slug ) : string {

            $url = Url::combinePath(SITE_URL, 'wp-admin/admin.php?page=' . $menu_slug);

            return $url;

        }

        private function adminRequestTo(string $admin_page, string $method = 'GET' ) : TestRequest {

            $request = TestRequest::fromFullUrl( $method, $this->adminUrlTo( $admin_page ) );

            $request->server->set('SCRIPT_FILENAME', ROOT_DIR . DS. 'wp-admin' . DS . 'admin.php');
            $request->server->set('SCRIPT_NAME', DS. 'wp-admin' . DS . 'admin.php' );
            $request->overrideGlobals();

            return $request;

        }

    }
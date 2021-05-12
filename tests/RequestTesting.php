<?php


    declare(strict_types = 1);


    namespace Tests;

    use WPEmerge\Support\Url;

    trait RequestTesting
    {

        private function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php' ) : string {

            $url = Url::combinePath(SITE_URL, 'wp-admin/' . $parent_page . '?page=' . $menu_slug);

            return $url;

        }

        private function adminRequestTo( string $admin_page, string $method = 'GET', string $parent_file = 'admin.php' ) : TestRequest {

            $request = TestRequest::fromFullUrl( $method, $this->adminUrlTo( $admin_page , $parent_file) );

            $request->server->set('SCRIPT_FILENAME', ROOT_DIR . DS. 'wp-admin' . DS . $parent_file);
            $request->server->set('SCRIPT_NAME', DS. 'wp-admin' . DS . $parent_file );
            $request->overrideGlobals();

            return $request;

        }

    }
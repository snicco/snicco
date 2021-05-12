<?php


    declare(strict_types = 1);


    namespace Tests;

    use WPEmerge\Facade\WP;
    use WPEmerge\Support\Url;

    trait RequestTesting
    {

        private function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') : string
        {

            $url = Url::combinePath(SITE_URL, 'wp-admin/'.$parent_page.'?page='.$menu_slug);

            return $url;

        }

        private function adminRequestTo(string $admin_page, string $method = 'GET', string $parent_file = 'admin.php') : TestRequest
        {

            $request = TestRequest::fromFullUrl($method, $this->adminUrlTo($admin_page, $parent_file));

            $request->server->set('SCRIPT_FILENAME', ROOT_DIR.DS.WP::wpAdminFolder().DS.$parent_file);
            $request->server->set('SCRIPT_NAME', DS.WP::wpAdminFolder().DS.$parent_file);
            $request->overrideGlobals();

            return $request;

        }

        private function ajaxRequest(string $action, $method = 'POST', string $path = 'admin-ajax.php' )
        {

            $request = TestRequest::fromFullUrl($method, $this->ajaxUrl($path));
            $request->request->set('action', $action);

            $request->server->set('SCRIPT_FILENAME', ROOT_DIR.DS.WP::wpAdminFolder().DS.'admin-ajax.php');
            $request->server->set('SCRIPT_NAME', DS.WP::wpAdminFolder().DS.'admin-ajax.php');
            $request->overrideGlobals();

            return $request;

        }

        private function ajaxUrl (string $path = 'admin-ajax.php') : string
        {

            return trim(SITE_URL, '/').DS.WP::wpAdminFolder().DS.$path;

        }



    }
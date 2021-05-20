<?php


    declare(strict_types = 1);


    namespace Tests\traits;;

    use Tests\stubs\TestRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Support\Url;

    trait CreateWpTestUrls
    {

        private function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') : string
        {

            return Url::combineAbsPath(SITE_URL, 'wp-admin/'.$parent_page.'?page='.$menu_slug);

        }

        private function adminRequestTo(string $admin_page, string $method = 'GET', string $parent_file = 'admin.php') : Request
        {

            $request = TestRequest::fromFullUrl($method, $this->adminUrlTo($admin_page, $parent_file));

            return $request->withQueryParams( ['page' => $admin_page] );

        }

        private function ajaxRequest(string $action, $method = 'POST', string $path = 'admin-ajax.php' ) :Request
        {

            $request = TestRequest::fromFullUrl($method, $this->ajaxUrl($path));

            return $request->withParsedBody(['action' => $action]);

        }

        private function ajaxUrl (string $path = 'admin-ajax.php') : string
        {

            return trim(SITE_URL, '/').DS.WP::wpAdminFolder().DS.$path;

        }



    }
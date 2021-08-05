<?php


    namespace Snicco\Testing\Concerns;

    use Snicco\Application\Application;
    use Snicco\Support\Url;
    use Snicco\Support\WP;

    /**
     * @property Application $app
     */
    trait BuildsWordPressUrls
    {

        protected function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') : string
        {

            return Url::combineAbsPath($this->app->config('app.url'), WP::wpAdminFolder().'/'.$parent_page.'?page='.$menu_slug);
        }

        protected function ajaxUrl(string $action) : string
        {

            return trim($this->app->config('app.url'), '/').'/'.WP::wpAdminFolder().'/admin-ajax.php?action='.$action;
        }

    }
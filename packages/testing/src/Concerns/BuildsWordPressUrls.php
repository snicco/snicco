<?php

namespace Snicco\Testing\Concerns;

use Snicco\Core\Support\Url;

trait BuildsWordPressUrls
{
    
    protected function baseUrl() :string
    {
        return 'https://example.com';
    }
    
    protected function adminDashboardPrefix() :string
    {
        return '/wp-admin';
    }
    
    final protected function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') :string
    {
        return Url::combineAbsPath(
            $this->baseUrl(),
            rtrim($this->adminDashboardPrefix(), '/').'/'.$parent_page.'?page='.$menu_slug
        );
    }
    
}
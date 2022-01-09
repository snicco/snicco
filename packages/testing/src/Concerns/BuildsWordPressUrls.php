<?php

namespace Snicco\Testing\Concerns;

use Snicco\Core\Support\Url;
use Snicco\Core\Routing\AdminDashboard;

trait BuildsWordPressUrls
{
    
    protected function baseUrl() :string
    {
        return 'https://example.com';
    }
    
    final protected function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') :string
    {
        return Url::combineAbsPath(
            $this->baseUrl(),
            $this->adminDashboard()->urlPrefix().'/'.$parent_page.'?page='.$menu_slug
        );
    }
    
    abstract protected function adminDashboard() :AdminDashboard;
    
}
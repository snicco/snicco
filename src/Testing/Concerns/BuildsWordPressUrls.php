<?php

namespace Snicco\Testing\Concerns;

use Snicco\Support\WP;
use Snicco\Support\Url;

trait BuildsWordPressUrls
{
    
    protected function baseUrl() :string
    {
        return 'https://example.com';
    }
    
    protected function adminUrlTo(string $menu_slug, string $parent_page = 'admin.php') :string
    {
        return Url::combineAbsPath(
            $this->baseUrl(),
            WP::wpAdminFolder().'/'.$parent_page.'?page='.$menu_slug
        );
    }
    
    protected function ajaxUrl(string $action = '') :string
    {
        $base = trim($this->baseUrl(), '/')
                .'/'
                .WP::wpAdminFolder()
                .'/admin-ajax.php';
        
        if ( ! empty($action)) {
            $base = "$base?=$action";
        }
        
        return $base;
    }
    
}
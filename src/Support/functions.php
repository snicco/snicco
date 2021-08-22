<?php

declare(strict_types=1);

namespace Snicco\Support\Functions
{
    
    function addMenuPage(string $menu_title, string $capability, string $menu_slug, string $icon_url, int $position = null)
    {
        
        add_menu_page(
            '',
            $menu_title,
            $capability,
            $menu_slug,
            function () { },
            $icon_url,
            $position
        );
        
    }
    
    function addSubMenuPage(string $parent_slug, string $menu_title, string $capability, string $menu_slug, int $position = null)
    {
        
        add_submenu_page(
            $parent_slug,
            '',
            $menu_title,
            $capability,
            $menu_slug,
            function () { },
            $position = null
        );
        
    }
    
    /**
     * Call the given Closure with the given value then return the value.
     * don't use the non-namespace illuminate function.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     *
     * @return mixed
     */
    function tap($value, ?callable $callback = null)
    {
        $callback($value);
        
        return $value;
    }
    
}
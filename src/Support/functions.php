<?php


    declare(strict_types = 1);


    namespace Snicco\Support\Functions {

        function addMenuPage (string $menu_title, string $capability, string $menu_slug, string $icon_url, int $position = null ) {

            add_menu_page('',$menu_title, $capability, $menu_slug, function () {}, $icon_url, $position);

        }

        function addSubMenuPage(string $parent_slug, string $menu_title, string $capability, string $menu_slug, int $position = null) {

            add_submenu_page($parent_slug, '', $menu_title, $capability, $menu_slug, function () {}, $position = null);

        }

    }
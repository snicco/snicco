<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use LogicException;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\StrArr\Str;

use function add_action;
use function add_menu_page;
use function add_submenu_page;

final class WPAdminMenu
{
    private AdminMenu $admin_menu;

    public function __construct(AdminMenu $admin_menu)
    {
        $this->admin_menu = $admin_menu;
    }

    public function setUp(string $text_domain = 'default'): void
    {
        add_action('admin_menu', function () use ($text_domain) {
            $this->buildMenu($text_domain);
        });
    }

    private function buildMenu(string $text_domain): void
    {
        $empty_cb = static function (): void {
        };

        $parents = [];
        $children = [];

        foreach ($this->admin_menu->items() as $item) {
            if ($item->isChild()) {
                $children[] = $item;
            } else {
                $parents[] = $item;
            }
        }

        foreach ($parents as $parent) {
            add_menu_page(
                __($parent->pageTitle(), $text_domain),
                __($parent->menuTitle(), $text_domain),
                $parent->requiredCapability() ?? 'manage_options',
                $this->parseSlug($parent->slug()->asString()),
                $empty_cb,
                $parent->icon() ?? '',
                $parent->position(),
            );
        }

        foreach ($children as $child) {
            add_submenu_page(
                $this->parseSlug($child->parentSlug()->asString()),
                __($child->pageTitle(), $text_domain),
                __($child->menuTitle(), $text_domain),
                $child->requiredCapability() ?? 'manage_options',
                $this->parseSlug($child->slug()->asString()),
                $empty_cb,
                $child->position(),
            );
        }
    }

    private function parseSlug(string $slug): string
    {
        if (! Str::contains($slug, '.php')) {
            throw new LogicException(
                "Admin menu item with slug [{$slug}] is miss configured as it does not contain a path-segment with '.php'.\nPlease check your admin.php route file."
            );
        }

        return Str::afterLast($slug, '/');
    }
}

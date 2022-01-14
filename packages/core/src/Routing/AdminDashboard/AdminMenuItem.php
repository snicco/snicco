<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use Snicco\Core\Support\UrlPath;
use Snicco\Core\Routing\Route\Route;

use function mb_convert_case;

/**
 * @api
 */
class AdminMenuItem
{
    
    const PAGE_TITLE = 'page_title';
    const MENU_TITLE = 'menu_title';
    const ICON = 'icon';
    const CAPABILITY = 'capability';
    const POSITION = 'position';
    
    private string  $page_title;
    private string  $menu_title;
    private string  $menu_slug;
    private ?string $capability;
    private ?string $icon;
    private ?int    $position;
    private ?string $parent_slug;
    
    private function __construct(
        string $page_title,
        string $menu_title,
        string $menu_slug,
        ?string $capability = null,
        ?string $icon = null,
        ?int $position = null,
        ?string $parent_slug = null
    ) {
        Assert::stringNotEmpty($page_title, '$page_title cant be empty.');
        $this->page_title = $page_title;
        
        Assert::stringNotEmpty($menu_title, '$menu_title cant be empty.');
        $this->menu_title = $menu_title;
        
        Assert::stringNotEmpty($menu_slug);
        $this->menu_slug = $menu_slug;
        
        if (null !== $capability) {
            Assert::stringNotEmpty($capability, '$capability has to be null or non empty string.');
        }
        $this->capability = $capability;
        
        if (null !== $icon) {
            Assert::stringNotEmpty($icon, '$icon has to be null or non empty string.');
        }
        $this->icon = $icon;
        
        $this->position = $position;
        
        if (null !== $parent_slug) {
            Assert::stringNotEmpty(
                $parent_slug,
                '$parent_slug has to be null or non empty string.'
            );
        }
        $this->parent_slug = $parent_slug;
    }
    
    /**
     * @interal
     */
    final public static function fromRoute(Route $route, array $attributes = [], ?string $parent_slug = null) :AdminMenuItem
    {
        $menu_slug = $route->getPattern();
        
        $page_title = null;
        $menu_title = null;
        
        if (isset($attributes[self::MENU_TITLE])) {
            $menu_title = $attributes[self::MENU_TITLE];
            $page_title = $menu_title;
        }
        
        if (isset($attributes[self::PAGE_TITLE])) {
            $page_title = $attributes[self::PAGE_TITLE];
            $menu_title = $menu_title ?? $page_title;
        }
        
        if (null === $page_title) {
            $page_title = self::stringToHeadline(Str::afterLast($route->getName(), '.'));
        }
        
        if (null === $menu_title) {
            $menu_title = $page_title;
        }
        
        return new self(
            $page_title,
            $menu_title,
            $menu_slug,
            $attributes[self::CAPABILITY] ?? null,
            $attributes[self::ICON] ?? null,
            $attributes[self::POSITION] ?? null,
            $parent_slug
        );
    }
    
    private static function stringToHeadline(string $route_name) :string
    {
        $parts = explode(' ', str_replace(['.', '_', '-'], ' ', $route_name));
        
        if (count($parts) > 1) {
            $parts = array_map(function (string $part) {
                return mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
            }, $parts);
        }
        
        return implode(' ', $parts);
    }
    
    final function pageTitle() :string
    {
        return $this->page_title;
    }
    
    final function menuTitle() :string
    {
        return $this->menu_title;
    }
    
    final function slug() :UrlPath
    {
        return UrlPath::fromString($this->menu_slug);
    }
    
    final function position() :?int
    {
        return $this->position;
    }
    
    final function requiredCapability() :?string
    {
        return $this->capability;
    }
    
    final function icon() :?string
    {
        return $this->icon;
    }
    
    public function parentSlug() :?UrlPath
    {
        return $this->parent_slug ? UrlPath::fromString($this->parent_slug) : null;
    }
    
}
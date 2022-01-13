<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

use Snicco\Support\Str;
use Snicco\Core\Support\UrlPath;
use Snicco\Core\Routing\Route\Route;

use function mb_convert_case;

/**
 * @api
 */
final class AdminMenuItem
{
    
    //$page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null
    
    private string  $page_title;
    private string  $menu_title;
    private string  $menu_slug;
    private ?string $capability = null;
    private ?string $icon       = null;
    private ?int    $position   = null;
    
    public function __construct(string $page_title, string $menu_title, string $menu_slug)
    {
        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->menu_slug = $menu_slug;
    }
    
    public static function fromRoute(Route $route) :AdminMenuItem
    {
        $page_title = $menu_title = self::headline(Str::afterLast($route->getName(), '.'));
        $menu_slug = Str::afterLast($route->getPattern(), '/');
        return new self($page_title, $menu_title, $menu_slug);
    }
    
    private static function headline(string $route_name) :string
    {
        $parts = explode(' ', str_replace(['.', '_'], ' ', $route_name));
        
        if (count($parts) > 1) {
            $parts = array_map(function (string $part) {
                return mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
            }, $parts);
        }
        
        return implode(' ', $parts);
    }
    
    public function pageTitle() :string
    {
        return $this->page_title;
    }
    
    public function menuTitle() :string
    {
        return $this->menu_title;
    }
    
    public function slug() :UrlPath
    {
        return UrlPath::fromString($this->menu_slug);
    }
    
    public function position() :?int
    {
        return $this->position;
    }
    
    public function requiredCapability() :?string
    {
        return $this->capability;
    }
    
    public function icon() :?string
    {
        return $this->icon;
    }
    
}
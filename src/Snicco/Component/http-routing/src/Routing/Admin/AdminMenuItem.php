<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use LogicException;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function count;
use function mb_convert_case;

final class AdminMenuItem
{
    /**
     * @var string
     */
    public const PAGE_TITLE = 'page_title';

    /**
     * @var string
     */
    public const MENU_TITLE = 'menu_title';

    /**
     * @var string
     */
    public const ICON = 'icon';

    /**
     * @var string
     */
    public const CAPABILITY = 'capability';

    /**
     * @var string
     */
    public const POSITION = 'position';

    private string $page_title;

    private string $menu_title;

    private string $menu_slug;

    private ?string $capability;

    private ?string $icon;

    private ?int $position;

    private ?string $parent_slug;

    public function __construct(
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
            Assert::stringNotEmpty($parent_slug, '$parent_slug has to be null or non empty string.');
        }

        $this->parent_slug = $parent_slug;
    }

    /**
     * @interal
     *
     * @param array{
     *     menu_title?: string,
     *     page_title?: string,
     *     icon?: string,
     *     capability?: string,
     *     position?: int
     * } $attributes
     */
    public static function fromRoute(
        Route $route,
        array $attributes = [],
        ?string $parent_slug = null
    ): AdminMenuItem {
        $menu_slug = $route->getPattern();

        $page_title = null;
        $menu_title = null;

        if (isset($attributes[self::MENU_TITLE])) {
            $menu_title = $attributes[self::MENU_TITLE];
            $page_title = $menu_title;
        }

        if (isset($attributes[self::PAGE_TITLE])) {
            $page_title = $attributes[self::PAGE_TITLE];
            $menu_title ??= $page_title;
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

    public function pageTitle(): string
    {
        return $this->page_title;
    }

    public function menuTitle(): string
    {
        return $this->menu_title;
    }

    public function slug(): UrlPath
    {
        return UrlPath::fromString($this->menu_slug);
    }

    public function position(): ?int
    {
        return $this->position;
    }

    public function requiredCapability(): ?string
    {
        return $this->capability;
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    public function parentSlug(): UrlPath
    {
        if (! $this->parent_slug) {
            throw new LogicException(sprintf('Menu item [%s] does not have a parent item.', $this->menu_slug));
        }

        return UrlPath::fromString($this->parent_slug);
    }

    public function isChild(): bool
    {
        return null !== $this->parent_slug;
    }

    private static function stringToHeadline(string $route_name): string
    {
        $parts = explode(' ', str_replace(['.', '_', '-'], ' ', $route_name));

        if (count($parts) > 1) {
            $parts = array_map(fn (string $part): string => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'), $parts);
        }

        return implode(' ', $parts);
    }
}

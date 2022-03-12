<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Webmozart\Assert\Assert;

use function explode;
use function ltrim;
use function rtrim;
use function trim;

final class WPAdminArea implements AdminArea
{
    private string $prefix;

    private string $login_path;

    public function __construct(string $admin_dashboard_url_prefix, string $login_path)
    {
        Assert::stringNotEmpty($admin_dashboard_url_prefix);
        Assert::stringNotEmpty($login_path);
        $this->prefix = '/' . ltrim($admin_dashboard_url_prefix, '/');
        $this->login_path = $login_path;
    }

    public static function fromDefaults(): WPAdminArea
    {
        return new self('/wp-admin', '/wp-login.php');
    }

    public function urlPrefix(): AdminAreaPrefix
    {
        return AdminAreaPrefix::fromString($this->prefix);
    }

    public function rewriteForUrlGeneration(string $route_pattern): array
    {
        $parts = explode('.php/', $route_pattern);

        $path = $parts[0] ?? '';
        $page = $parts[1] ?? '';

        Assert::stringNotEmpty($path);
        Assert::stringNotEmpty($page);

        return [
            $path . '.php',
            [
                'page' => trim($page, '/'),
            ],
        ];
    }

    public function rewriteForRouting(Request $request): string
    {
        $path = $request->path();
        $page = (string) $request->query('page');
        if ('' === $page) {
            return $request->path();
        }

        return rtrim($path, '/') . '/' . $page;
    }

    public function loginPath(): string
    {
        return $this->login_path;
    }
}

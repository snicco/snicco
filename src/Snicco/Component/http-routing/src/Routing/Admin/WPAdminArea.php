<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use Closure;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Webmozart\Assert\Assert;

use function explode;
use function is_string;
use function ltrim;
use function parse_url;
use function rtrim;
use function trim;

use const PHP_URL_PATH;

final class WPAdminArea implements AdminArea
{
    private string $prefix;

    /**
     * @var non-empty-string|Closure():string
     */
    private $login_path;

    /**
     * @param string|Closure():string $login_path
     */
    public function __construct(string $admin_dashboard_url_prefix, $login_path)
    {
        Assert::stringNotEmpty($admin_dashboard_url_prefix);
        $this->prefix = '/' . ltrim($admin_dashboard_url_prefix, '/');
        if (is_string($login_path)) {
            Assert::stringNotEmpty($login_path);
            $this->login_path = '/' . ltrim($login_path, '/');
        } else {
            $this->login_path = $login_path;
        }
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
        $login_path = is_string($this->login_path)
            ? $this->login_path
            : $this->callbackToPath($this->login_path);

        return UrlPath::fromString($login_path)->asString();
    }

    private function callbackToPath(Closure $callback): string
    {
        $url = ($callback)();

        Assert::stringNotEmpty($url);

        $path = parse_url($url, PHP_URL_PATH);

        Assert::stringNotEmpty($path, "URL '{$url}' is not valid. 'path' is missing.");

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Routing\AdminDashboardPrefix;

use function rtrim;
use function ltrim;
use function explode;

/**
 * @interal
 */
final class WPAdminDashboard implements AdminDashboard
{
    
    private string $prefix;
    private string $loading_script_identifier;
    private string $login_path;
    
    public function __construct(string $admin_dashboard_url_prefix, string $loading_script_identifier, string $login_path)
    {
        Assert::stringNotEmpty($admin_dashboard_url_prefix);
        Assert::notStartsWith($loading_script_identifier, '/');
        $this->prefix = '/'.ltrim($admin_dashboard_url_prefix, '/');
        $this->loading_script_identifier = $loading_script_identifier;
        $this->login_path = $login_path;
    }
    
    public static function fromDefaults() :WPAdminDashboard
    {
        return new self('/wp-admin', 'wp-admin', '/wp-login.php');
    }
    
    public function urlPrefix() :AdminDashboardPrefix
    {
        return AdminDashboardPrefix::fromString($this->prefix);
    }
    
    public function goesTo(Request $request) :bool
    {
        if (Str::contains($request->loadingScript(), 'admin-ajax.php')) {
            return false;
        }
        
        return Str::startsWith($request->loadingScript(), $this->loading_script_identifier);
    }
    
    public function rewriteForUrlGeneration(string $route_pattern) :array
    {
        $parts = explode('.php/', $route_pattern);
        
        Assert::keyExists($parts, 0);
        Assert::keyExists($parts, 1);
        Assert::stringNotEmpty($parts[0]);
        Assert::stringNotEmpty($parts[1]);
        
        return [
            $parts[0].'.php',
            [
                'page' => $parts[1],
            ],
        ];
    }
    
    public function rewriteForRouting(Request $request) :string
    {
        $path = $request->path();
        $page = $request->query('page');
        
        if ( ! $page) {
            return $request->path();
        }
        
        return rtrim($path, '/').'/'.$page;
    }
    
    public function loginPath() :string
    {
        return $this->login_path;
    }
    
}
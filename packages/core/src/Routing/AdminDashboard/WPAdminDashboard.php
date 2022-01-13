<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;

use function rtrim;
use function ltrim;
use function explode;

/**
 * @interal
 */
final class WPAdminDashboard implements AdminDashboard
{
    
    private string $prefix;
    private string $login_path;
    
    public function __construct(string $admin_dashboard_url_prefix, string $login_path)
    {
        Assert::stringNotEmpty($admin_dashboard_url_prefix);
        $this->prefix = '/'.ltrim($admin_dashboard_url_prefix, '/');
        $this->login_path = $login_path;
    }
    
    public static function fromDefaults() :WPAdminDashboard
    {
        return new self('/wp-admin', '/wp-login.php');
    }
    
    public function urlPrefix() :AdminDashboardPrefix
    {
        return AdminDashboardPrefix::fromString($this->prefix);
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
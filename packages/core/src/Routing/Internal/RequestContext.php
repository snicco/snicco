<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\AdminDashboard;

/**
 * @interal
 */
final class RequestContext
{
    
    private Request        $request;
    private AdminDashboard $admin_dashboard;
    private bool           $force_https;
    private string         $host;
    private string         $scheme;
    
    public function __construct(Request $request, AdminDashboard $admin_dashboard, bool $force_https = false)
    {
        $this->request = $request;
        $this->force_https = $force_https;
        $this->admin_dashboard = $admin_dashboard;
        $host = $request->getUri()->getHost();
        Assert::stringNotEmpty($host, 'Request URI has no host.');
        $this->host = $host;
        $scheme = $request->getUri()->getScheme();
        Assert::stringNotEmpty($scheme, 'Request URI has no scheme.');
        $this->scheme = $scheme;
    }
    
    public function getHost() :string
    {
        return $this->host;
    }
    
    public function getScheme() :string
    {
        return $this->scheme;
    }
    
    public function getHttpPort() :int
    {
        return $this->request->getUri()->getPort() ?? 80;
    }
    
    public function getHttpsPort() :int
    {
        return $this->request->getUri()->getPort() ?? 443;
    }
    
    public function shouldForceHttps() :bool
    {
        return $this->force_https;
    }
    
    public function isSecure() :bool
    {
        return $this->getScheme() === 'https';
    }
    
    public function referer() :?string
    {
        $ref = $this->request->getHeaderLine('referer');
        return empty($ref) ? null : $ref;
    }
    
    public function path() :string
    {
        return $this->request->path();
    }
    
    public function adminDashboard() :AdminDashboard
    {
        return $this->admin_dashboard;
    }
    
    public function uriAsString() :string
    {
        return (string) $this->request->getUri();
    }
    
}
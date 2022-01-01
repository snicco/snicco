<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\AdminDashboard;

/**
 * @interal
 */
final class RequestContext
{
    
    private Request        $request;
    private AdminDashboard $admin_path;
    private bool           $force_https;
    
    public function __construct(Request $request, AdminDashboard $admin_path, bool $force_https = false)
    {
        $this->request = $request;
        $this->force_https = $force_https;
        $this->admin_path = $admin_path;
    }
    
    public function getHost() :string
    {
        return $this->request->getUri()->getHost();
    }
    
    public function getScheme() :string
    {
        return $this->request->getUri()->getScheme();
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
        return $this->admin_path;
    }
    
    public function uriAsString() :string
    {
        return (string) $this->request->getUri();
    }
    
}
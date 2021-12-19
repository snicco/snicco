<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;

final class UrlGenerationContext
{
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var bool
     */
    private $with_trailing_slashes;
    
    /**
     * @var bool
     */
    private $force_https;
    
    public function __construct(Request $request, bool $with_trailing_slashes = false, bool $force_https = false)
    {
        $this->request = $request;
        $this->with_trailing_slashes = $with_trailing_slashes;
        $this->force_https = $force_https;
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
    
    public function withTrailingSlashes() :bool
    {
        return $this->with_trailing_slashes;
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
    
    public function uriAsString() :string
    {
        return (string) $this->request->getUri();
    }
    
}
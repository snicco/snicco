<?php

declare(strict_types=1);

namespace Snicco\Http\Psr7;

use Snicco\Support\Arr;
use Snicco\Support\Str;

trait InspectsRequest
{
    
    public function realMethod()
    {
        return Arr::get($this->getServerParams(), 'REQUEST_METHOD', 'GET');
    }
    
    public function isGet() :bool
    {
        return $this->isMethod('GET');
    }
    
    public function isHead() :bool
    {
        return $this->isMethod('HEAD');
    }
    
    public function isPost() :bool
    {
        return $this->isMethod('POST');
    }
    
    public function isPut() :bool
    {
        return $this->isMethod('PUT');
    }
    
    public function isPatch() :bool
    {
        return $this->isMethod('PATCH');
    }
    
    public function isDelete() :bool
    {
        return $this->isMethod('DELETE');
    }
    
    public function isOptions() :bool
    {
        return $this->isMethod('OPTIONS');
    }
    
    public function isReadVerb() :bool
    {
        return $this->isMethodSafe();
    }
    
    public function isMethodSafe() :bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
    }
    
    public function isAjax() :bool
    {
        return $this->isXmlHttpRequest();
    }
    
    public function isXmlHttpRequest() :bool
    {
        return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
    }
    
    public function isSendingJson() :bool
    {
        return Str::contains($this->getHeaderLine('Content-Type'), ['/json', '+json']);
    }
    
    public function isExpectingJson() :bool
    {
        $accepts = $this->acceptableContentTypes(false);
        
        return Str::contains($accepts, ['/json', '+json']);
    }
    
    public function acceptableContentTypes(bool $as_array = true)
    {
        return $as_array ? $this->getHeader('Accept') : $this->getHeaderLine('Accept');
    }
    
    public function acceptsHtml() :bool
    {
        return $this->accepts('text/html');
    }
    
    public function accepts(string $content_type) :bool
    {
        $accepts = $this->acceptableContentTypes();
        
        return $this->matchesType($content_type, $accepts);
    }
    
    public function acceptsOneOf(array $content_types) :bool
    {
        $accepts = $this->acceptableContentTypes();
        
        foreach ($content_types as $content_type) {
            if ($this->matchesType($content_type, $accepts)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isMethod(string $method) :bool
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }
    
    private function matchesType(string $match_against, array $content_types) :bool
    {
        if ($content_types === []) {
            return true;
        }
        
        foreach ($content_types as $content_type) {
            if ($content_type === '*/*' || $content_type === '*') {
                return true;
            }
            
            if ($content_type === strtok($match_against, '/').'/*') {
                return true;
            }
        }
        
        return in_array($match_against, $content_types);
    }
    
}
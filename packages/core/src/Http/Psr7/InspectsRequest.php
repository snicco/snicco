<?php

declare(strict_types=1);

namespace Snicco\Core\Http\Psr7;

use Snicco\StrArr\Arr;
use Snicco\StrArr\Str;

trait InspectsRequest
{
    
    final public function realMethod()
    {
        return Arr::get($this->getServerParams(), 'REQUEST_METHOD', 'GET');
    }
    
    final public function isGet() :bool
    {
        return $this->isMethod('GET');
    }
    
    final public function isHead() :bool
    {
        return $this->isMethod('HEAD');
    }
    
    final public function isPost() :bool
    {
        return $this->isMethod('POST');
    }
    
    final public function isPut() :bool
    {
        return $this->isMethod('PUT');
    }
    
    final public function isPatch() :bool
    {
        return $this->isMethod('PATCH');
    }
    
    final public function isDelete() :bool
    {
        return $this->isMethod('DELETE');
    }
    
    final public function isOptions() :bool
    {
        return $this->isMethod('OPTIONS');
    }
    
    final public function isReadVerb() :bool
    {
        return $this->isMethodSafe();
    }
    
    final public function isMethodSafe() :bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
    }
    
    final public function isAjax() :bool
    {
        return $this->isXmlHttpRequest();
    }
    
    final public function isXmlHttpRequest() :bool
    {
        return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
    }
    
    final public function isSendingJson() :bool
    {
        return Str::contains($this->getHeaderLine('Content-Type'), ['/json', '+json']);
    }
    
    final public function isExpectingJson() :bool
    {
        $accepts = $this->acceptableContentTypes(false);
        
        return Str::contains($accepts, ['/json', '+json']);
    }
    
    final public function acceptableContentTypes(bool $as_array = true)
    {
        return $as_array ? $this->getHeader('Accept') : $this->getHeaderLine('Accept');
    }
    
    final public function acceptsHtml() :bool
    {
        return $this->accepts('text/html');
    }
    
    final public function accepts(string $content_type) :bool
    {
        $accepts = $this->acceptableContentTypes();
        
        return $this->matchesType($content_type, $accepts);
    }
    
    final public function acceptsOneOf(array $content_types) :bool
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
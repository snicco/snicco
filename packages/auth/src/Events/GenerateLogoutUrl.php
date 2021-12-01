<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\EventDispatcher\Events\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedFilter;

class GenerateLogoutUrl extends CoreEvent implements MappedFilter
{
    
    public string $redirect_to;
    public string $url;
    
    public function __construct(string $url, string $redirect_to = '/')
    {
        $this->url = $url;
        $this->redirect_to = $redirect_to;
    }
    
    public function filterableAttribute()
    {
        return $this->url;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}
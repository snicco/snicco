<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Support\Str;
use Snicco\Core\Support\WP;
use Snicco\Core\Contracts\HasCustomRoutePath;

class AdminRoute extends Route implements HasCustomRoutePath
{
    
    public function toPath() :string
    {
        $url = $this->getUrl();
        
        $parts = explode('/', Str::after(ltrim($url, '/'), '/'));
        
        return WP::wpAdminFolder().'/'.$parts[0].'?page='.$parts[1];
    }
    
}
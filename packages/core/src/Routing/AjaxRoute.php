<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Support\Str;
use Snicco\Core\Support\WP;
use Snicco\Core\Contracts\HasCustomRoutePath;

class AjaxRoute extends Route implements HasCustomRoutePath
{
    
    public function toPath() :string
    {
        $methods = $this->getMethods();
        
        $base_path = WP::wpAdminFolder().'/admin-ajax.php';
        
        if (in_array('GET', $methods, true)) {
            $action = Str::afterLast($this->getUrl(), '.php/');
            return $base_path.'?'.'action='.$action;
        }
        
        return $base_path;
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Support\WP;
use Snicco\Core\Support\Str;
use Snicco\Core\Support\Arr;
use Snicco\Core\Contracts\ConvertsToUrl;

class AjaxRoute extends Route implements ConvertsToUrl
{
    
    public function toUrl(array $arguments = []) :string
    {
        $methods = $this->getMethods();
        
        $base_url = WP::adminUrl('admin-ajax.php');
        
        if (in_array('GET', $methods, true)) {
            $arguments = array_merge(
                ['action' => Str::afterLast($this->getUrl(), '.php/')],
                $arguments
            );
            
            return $base_url.'?'.Arr::query($arguments);
        }
        
        return $base_url;
    }
    
}
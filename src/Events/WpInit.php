<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;
use BetterWpHooks\Traits\IsAction;

class WpInit extends Event
{
    
    use IsAction;
    
    /**
     * @var Config
     */
    public $config;
    
    /**
     * @var Request
     */
    public $request;
    
    public function __construct(Config $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
        
    }
    
}
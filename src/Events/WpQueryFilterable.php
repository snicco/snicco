<?php

declare(strict_types=1);

namespace Snicco\Events;

use WP;
use Snicco\Http\Psr7\Request;

class WpQueryFilterable extends Event
{
    
    public bool    $do_request = true;
    public Request $server_request;
    public WP      $wp;
    
    public function __construct(Request $server_request, bool $do_request, WP $wp)
    {
        $this->server_request = $server_request;
        $this->do_request = $do_request;
        $this->wp = $wp;
    }
    
    public function default() :bool
    {
        return $this->do_request;
    }
    
}
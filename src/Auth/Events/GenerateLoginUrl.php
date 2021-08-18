<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\Support\WP;
use Snicco\Events\Event;

class GenerateLoginUrl extends Event
{
    
    public string $redirect_to;
    
    public bool $force_reauth;
    
    public function __construct(string $url, string $redirect_to = null, bool $force_reauth = false)
    {
        $this->redirect_to = $redirect_to ?? WP::adminUrl();
        $this->force_reauth = $force_reauth;
    }
    
}
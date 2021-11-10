<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use WP_User;
use Snicco\Events\Event;

class SettingAuthCookie extends Event
{
    
    public WP_User $user;
    
    public int $user_id;
    
    public int $expiration;
    
    public string $scheme;
    
    private string $cookie;
    
    public function __construct($cookie, $user_id, $expiration, $scheme)
    {
        $this->cookie = $cookie;
        $this->user_id = $user_id;
        $this->user = get_user_by('id', $this->user_id);
        $this->expiration = $expiration;
        $this->scheme = $scheme;
    }
    
    public function default() :string
    {
        return $this->cookie;
    }
    
}
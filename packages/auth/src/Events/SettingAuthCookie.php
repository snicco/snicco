<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use WP_User;
use Snicco\EventDispatcher\Events\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedFilter;

class SettingAuthCookie extends CoreEvent implements MappedFilter
{
    
    public string  $cookie;
    public WP_User $user;
    public int     $user_id;
    public int     $expiration;
    public string  $scheme;
    
    public function __construct($cookie, $user_id, $expiration, $scheme)
    {
        $this->cookie = $cookie;
        $this->user_id = $user_id;
        $this->user = get_user_by('id', $this->user_id);
        $this->expiration = $expiration;
        $this->scheme = $scheme;
    }
    
    public function filterableAttribute()
    {
        return $this->cookie;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}
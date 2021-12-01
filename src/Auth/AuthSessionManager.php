<?php

declare(strict_types=1);

namespace Snicco\Auth;

use Snicco\Http\Cookie;
use Snicco\Support\Arr;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Session\SessionManager;
use Snicco\Traits\InteractsWithTime;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Contracts\SessionManagerInterface;

class AuthSessionManager implements SessionManagerInterface
{
    
    use InteractsWithTime;
    
    private SessionManager $manager;
    private ?Session       $active_session = null;
    private SessionDriver  $driver;
    private array          $auth_config;
    /** @var callable|null */
    private $idle_resolver;
    
    public function __construct(SessionManager $manager, SessionDriver $driver, array $auth_config)
    {
        $this->manager = $manager;
        $this->driver = $driver;
        $this->auth_config = $auth_config;
    }
    
    public function start(Request $request, int $user_id) :Session
    {
        if ($session = $this->activeSession()) {
            return $session;
        }
        
        $this->active_session = $this->manager->start($request, $user_id);
        
        return $this->active_session;
    }
    
    public function activeSession() :?Session
    {
        if ($this->active_session instanceof Session) {
            return $this->active_session;
        }
        
        return null;
    }
    
    public function setIdleResolver(callable $idle_resolver)
    {
        $this->idle_resolver = $idle_resolver;
    }
    
    public function sessionCookie() :Cookie
    {
        return $this->manager->sessionCookie();
    }
    
    public function save()
    {
        $this->manager->save();
    }
    
    public function collectGarbage()
    {
        $this->manager->collectGarbage();
    }
    
    public function getAllForUser() :array
    {
        $sessions = $this->active_session->getAllForUser();
        
        $_s = [];
        
        foreach ($sessions as $session) {
            $session = (object) $session;
            if ($this->valid($session->payload)) {
                $_s[$session->id] = $session->payload;
            }
        }
        
        return $_s;
    }
    
    public function idleTimeout()
    {
        $timeout = Arr::get($this->auth_config, 'idle', 0);
        
        if (is_callable($this->idle_resolver)) {
            return call_user_func($this->idle_resolver, $timeout);
        }
        
        return $timeout;
    }
    
    public function confirmationDuration() :int
    {
        return Arr::get($this->auth_config, 'confirmation.duration', 0);
    }
    
    public function allowsPersistentLogin() :bool
    {
        return Arr::get($this->auth_config, 'features.remember_me', false) === true;
    }
    
    public function destroyOthersForUser(string $hashed_token, int $user_id)
    {
        $this->driver->destroyOthersForUser($hashed_token, $user_id);
    }
    
    public function destroyAllForUser(int $user_id)
    {
        $this->driver->destroyAllForUser($user_id);
    }
    
    public function destroyAll()
    {
        $this->driver->destroyAll();
    }
    
    private function valid(array $session_payload) :bool
    {
        if ($this->isExpired($session_payload)) {
            return false;
        }
        
        if ($this->isIdle($session_payload) && ! $this->allowsPersistentLogin()) {
            return false;
        }
        
        return true;
    }
    
    private function isExpired(array $session_payload) :bool
    {
        $expires = $session_payload['_expires_at'] ?? 0;
        
        return $expires < $this->currentTime();
    }
    
    private function isIdle(array $session_payload) :bool
    {
        $last_activity = $session_payload['_last_activity'] ?? 0;
        
        return ($this->currentTime() - $last_activity) > $this->idleTimeout();
    }
    
}
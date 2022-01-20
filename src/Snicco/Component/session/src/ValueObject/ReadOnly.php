<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use Snicco\Component\Session\Session;
use Snicco\Component\Session\ImmutableSession;

/**
 * @api
 */
final class ReadOnly implements ImmutableSession
{
    
    private Session $session;
    
    private function __construct(Session $session)
    {
        $this->session = $session;
    }
    
    public static function fromSession(Session $session) :ReadOnly
    {
        return new ReadOnly($session);
    }
    
    public function all() :array
    {
        return $this->session->all();
    }
    
    public function boolean(string $key, bool $default = false) :bool
    {
        return $this->session->boolean($key, $default);
    }
    
    public function createdAt() :int
    {
        return $this->session->createdAt();
    }
    
    public function csrfToken() :CsrfToken
    {
        return $this->session->csrfToken();
    }
    
    public function exists($keys) :bool
    {
        return $this->session->exists($keys);
    }
    
    public function get(string $key, $default = null)
    {
        return $this->session->get($key, $default);
    }
    
    public function has(string $key) :bool
    {
        return $this->session->has($key);
    }
    
    public function hasOldInput(string $key = null) :bool
    {
        return $this->session->hasOldInput($key);
    }
    
    public function id() :SessionId
    {
        return $this->session->id();
    }
    
    public function lastActivity() :int
    {
        return $this->session->lastActivity();
    }
    
    public function lastRotation() :int
    {
        return $this->session->lastRotation();
    }
    
    public function missing($keys) :bool
    {
        return $this->session->missing($keys);
    }
    
    public function oldInput(string $key = null, $default = null)
    {
        return $this->session->oldInput($key, $default);
    }
    
    public function only($keys) :array
    {
        return $this->session->only($keys);
    }
    
}
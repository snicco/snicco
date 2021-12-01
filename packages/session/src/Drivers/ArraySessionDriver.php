<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Traits\InteractsWithTime;
use Snicco\Session\Contracts\SessionDriver;

class ArraySessionDriver implements SessionDriver
{
    
    use InteractsWithTime;
    
    private array   $storage = [];
    private int     $lifetime_in_seconds;
    private Request $request;
    
    public function __construct(int $lifetime_in_seconds)
    {
        $this->lifetime_in_seconds = $lifetime_in_seconds;
    }
    
    public function open($savePath, $sessionName)
    {
        return true;
    }
    
    public function close()
    {
        return true;
    }
    
    public function read($sessionId)
    {
        if ( ! isset($this->storage[$sessionId])) {
            return '';
        }
        
        $session = $this->storage[$sessionId];
        
        $expiration = $this->calculateExpiration($this->lifetime_in_seconds);
        
        if (isset($session['time']) && $session['time'] >= $expiration) {
            return $session['payload'];
        }
        
        return '';
    }
    
    public function isValid(string $id) :bool
    {
        if ( ! isset($this->storage[$id])) {
            return false;
        }
        
        $session = $this->storage[$id];
        
        $expiration = $this->calculateExpiration($this->lifetime_in_seconds);
        
        if ( ! isset($session['time']) && $session['time'] < $expiration) {
            return false;
        }
        
        return true;
    }
    
    public function write($sessionId, $data)
    {
        $this->storage[$sessionId] = [
            'payload' => $data,
            'time' => $this->currentTime(),
            'user_id' => WP::userId(),
        ];
        
        return true;
    }
    
    public function destroy($sessionId)
    {
        if (isset($this->storage[$sessionId])) {
            unset($this->storage[$sessionId]);
        }
        
        return true;
    }
    
    public function gc($lifetime)
    {
        $expiration = $this->calculateExpiration($lifetime);
        
        foreach ($this->storage as $sessionId => $session) {
            if ($session['time'] < $expiration) {
                unset($this->storage[$sessionId]);
            }
        }
        
        return true;
    }
    
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    public function getAllByUserId(int $user_id) :array
    {
        $user_sessions = [];
        
        foreach ($this->storage as $key => $session) {
            if ($session['user_id'] === $user_id) {
                $session = (object) $session;
                $session->id = $key;
                $user_sessions[] = $session;
            }
        }
        return $user_sessions;
    }
    
    public function destroyOthersForUser(string $hashed_token, int $user_id)
    {
        foreach ($this->storage as $id => $session) {
            if ($session['user_id'] !== $user_id) {
                continue;
            }
            
            if ($id === $hashed_token) {
                continue;
            }
            
            unset($this->storage[$id]);
        }
    }
    
    public function destroyAllForUser(int $user_id)
    {
        foreach ($this->storage as $id => $session) {
            if ($session['user_id'] !== $user_id) {
                continue;
            }
            
            unset($this->storage[$id]);
        }
    }
    
    public function destroyAll()
    {
        $this->storage = [];
    }
    
    public function all() :array
    {
        return $this->storage;
    }
    
    private function calculateExpiration(int $seconds) :int
    {
        return $this->currentTime() - $seconds;
    }
    
}
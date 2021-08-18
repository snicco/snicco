<?php

declare(strict_types=1);

namespace Snicco\Session;

use Closure;
use stdClass;
use DateInterval;
use Carbon\Carbon;
use DateTimeInterface;
use Snicco\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\InteractsWithTime;
use Snicco\Session\Contracts\SessionDriver;
use Illuminate\Contracts\Support\MessageProvider;

class Session
{
    
    use InteractsWithTime;
    
    private string        $id;
    private array         $attributes               = [];
    private SessionDriver $driver;
    private bool          $started                  = false;
    private array         $initial_attributes       = [];
    private array         $loaded_data_from_handler = [];
    private int           $token_strength_in_bytes;
    
    public function __construct(SessionDriver $handler, int $token_strength_in_bytes = 32)
    {
        
        $this->driver = $handler;
        $this->token_strength_in_bytes = $token_strength_in_bytes;
        
    }
    
    public function start(string $session_id = '') :bool
    {
        
        $this->setId($session_id);
        $this->loadDataFromDriver();
        
        $this->started = true;
        
        $this->initial_attributes = $this->attributes;
        
        return $this->started;
        
    }
    
    private function loadDataFromDriver()
    {
        
        $data = $this->readFromDriver();
        
        $this->loaded_data_from_handler = $data;
        
        $this->attributes = Arr::mergeRecursive($this->attributes, $data);
        
    }
    
    private function readFromDriver() :array
    {
        
        if ($data = $this->driver->read($this->hash($this->getId()))) {
            
            $data = @unserialize($this->prepareForUnserialize($data));
            
            if ($data !== false && ! is_null($data) && is_array($data)) {
                return $data;
            }
        }
        
        return [];
    }
    
    private function hash(string $id)
    {
        
        if (function_exists('hash')) {
            return hash('sha256', $id);
        }
        else {
            return sha1($id);
        }
    }
    
    public function getId() :string
    {
        
        return $this->id;
    }
    
    public function setId(string $id) :Session
    {
        
        $this->id = $this->isValidId($id)
            ? $id
            : $this->generateSessionId();
        
        return $this;
        
    }
    
    protected function prepareForUnserialize(string $data) :string
    {
        
        return $data;
        
    }
    
    public function save() :void
    {
        
        $this->ageFlashData();
        
        $this->setLastActivity($this->currentTime());
        
        $this->driver->write(
            $this->hash($this->getId()),
            $this->prepareForStorage(serialize($this->attributes))
        );
        
    }
    
    private function ageFlashData() :void
    {
        
        $this->forget($this->get('_flash.old', []));
        
        $this->put('_flash.old', $this->get('_flash.new', []));
        
        $this->put('_flash.new', []);
        
    }
    
    public function forget($keys) :void
    {
        
        Arr::forget($this->attributes, $keys);
    }
    
    public function get(string $key, $default = null)
    {
        
        return Arr::get($this->attributes, $key, $default);
    }
    
    public function put($key, $value = null) :void
    {
        
        if ( ! is_array($key)) {
            $key = [$key => $value];
        }
        
        foreach ($key as $arrayKey => $arrayValue) {
            Arr::set($this->attributes, $arrayKey, $arrayValue);
        }
    }
    
    public function setLastActivity(int $timestamp)
    {
        
        $this->put('_last_activity', $timestamp);
        
    }
    
    protected function prepareForStorage(string $data) :string
    {
        
        return $data;
    }
    
    public function wasChanged() :bool
    {
        
        return $this->initial_attributes !== $this->attributes;
    }
    
    public function all() :array
    {
        
        return $this->attributes;
    }
    
    public function only($keys) :array
    {
        
        return Arr::only($this->attributes, $keys);
        
    }
    
    public function missing($key) :bool
    {
        
        return ! $this->exists($key);
    }
    
    public function exists($key) :bool
    {
        
        $placeholder = new stdClass;
        
        return ! collect(
            is_array($key)
                ? $key
                : func_get_args()
        )->contains(function ($key) use ($placeholder) {
            
            return $this->get($key, $placeholder) === $placeholder;
        });
    }
    
    public function hasOldInput(string $key = null) :bool
    {
        
        $old = $this->getOldInput($key);
        
        return is_null($key)
            ? count($old) > 0
            : ! is_null($old);
    }
    
    public function getOldInput(string $key = null, $default = null)
    {
        
        return Arr::get($this->get('_old_input', []), $key, $default);
    }
    
    public function replace(array $attributes) :void
    {
        
        $this->put($attributes);
    }
    
    public function remember(string $key, Closure $callback)
    {
        
        if ( ! is_null($value = $this->get($key))) {
            return $value;
        }
        
        return tap($callback(), function ($value) use ($key) {
            
            $this->put($key, $value);
            
        });
    }
    
    public function decrement($key, $amount = 1) :int
    {
        return $this->increment($key, $amount * -1);
    }
    
    public function increment(string $key, int $amount = 1, int $start_value = 0) :int
    {
        
        if ( ! $this->has($key)) {
            $this->put($key, $start_value);
        }
        
        $this->put($key, $value = $this->get($key, 0) + $amount);
        
        return $value;
    }
    
    public function has($key) :bool
    {
        
        return ! collect(
            is_array($key)
                ? $key
                : func_get_args()
        )->contains(function ($key) {
            
            return is_null($this->get($key));
        });
    }
    
    public function flashInputNow(array $input)
    {
        $this->now('_old_input', $input);
    }
    
    public function now(string $key, $value) :void
    {
        $this->put($key, $value);
        
        $this->push('_flash.old', $key);
    }
    
    public function push(string $key, $value) :void
    {
        
        $array = $this->get($key, []);
        
        $array[] = $value;
        
        $this->put($key, $array);
    }
    
    public function reflash() :void
    {
        $this->mergeNewFlashes($this->get('_flash.old', []));
        
        $this->put('_flash.old', []);
    }
    
    private function mergeNewFlashes(array $keys) :void
    {
        
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));
        
        $this->put('_flash.new', $values);
    }
    
    public function keep($keys = null) :void
    {
        
        $this->mergeNewFlashes(
            $keys = is_array($keys)
                ? $keys
                : func_get_args()
        );
        
        $this->removeFromOldFlashData($keys);
    }
    
    private function removeFromOldFlashData(array $keys) :void
    {
        
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }
    
    public function flashInput(array $value) :void
    {
        $this->flash('_old_input', $value);
    }
    
    public function flash(string $key, $value = true) :void
    {
        $this->put($key, $value);
        
        $this->push('_flash.new', $key);
        
        $this->removeFromOldFlashData([$key]);
    }
    
    public function invalidate() :bool
    {
        
        $this->flush();
        
        return $this->migrate();
        
    }
    
    public function flush() :void
    {
        
        $this->attributes = [];
    }
    
    private function migrate(bool $destroy_old = true) :bool
    {
        
        if ($destroy_old) {
            $this->driver->destroy($this->hash($this->getId()));
        }
        
        $this->setId($this->generateSessionId());
        
        return true;
    }
    
    private function generateSessionId() :string
    {
        
        return bin2hex(random_bytes($this->token_strength_in_bytes));
        
    }
    
    public function regenerate(bool $destroy_old = true) :bool
    {
        
        return $this->migrate($destroy_old);
    }
    
    public function isStarted() :bool
    {
        
        return $this->started;
    }
    
    public function setUserId(int $user_id)
    {
        
        $this->put('_user.id', $user_id);
        
    }
    
    public function isValidId(string $id) :bool
    {
        
        return (strlen($id) === 2 * $this->token_strength_in_bytes)
               && ctype_alnum($id)
               && $this->getDriver()->isValid($this->hash($id));
        
    }
    
    public function getPreviousUrl(?string $fallback = '/') :?string
    {
        
        return $this->get('_url.previous', $fallback);
    }
    
    public function setPreviousUrl(string $url) :void
    {
        
        $this->put('_url.previous', $url);
    }
    
    public function getIntendedUrl(string $default = '')
    {
        
        return $this->pull('_url.intended', $default);
        
    }
    
    public function pull(string $key, $default = null)
    {
        
        return Arr::pull($this->attributes, $key, $default);
    }
    
    /**
     * @param  DateTimeInterface|DateInterval|int  $delay
     */
    public function confirmAuthUntil($delay)
    {
        
        $ts = $this->availableAt($delay);
        
        $this->put('auth.confirm.until', $ts);
        
    }
    
    public function hasValidAuthConfirmToken() :bool
    {
        
        return Carbon::now()->getTimestamp() < $this->get('auth.confirm.until', 0);
        
    }
    
    public function setIntendedUrl(string $encoded_url)
    {
        
        $this->put('_url.intended', $encoded_url);
        
    }
    
    /**
     * Flash a container of errors to the session.
     *
     * @param  MessageProvider|array  $provider
     * @param  string  $bag
     *
     * @return $this
     */
    public function withErrors($provider, string $bag = 'default') :Session
    {
        $value = $this->toMessageBag($provider);
        
        $this->flash('errors', $this->errors()->put($bag, $value));
        
        return $this;
    }
    
    private function toMessageBag($provider) :MessageBag
    {
        if ($provider instanceof MessageProvider) {
            return $provider->getMessageBag();
        }
        
        return new MessageBag((array) $provider);
    }
    
    public function errors() :ViewErrorBag
    {
        $errors = $this->get('errors', new ViewErrorBag());
        
        if ( ! $errors instanceof ViewErrorBag) {
            $errors = new ViewErrorBag;
        }
        
        return $errors;
    }
    
    public function allowAccessToRoute(string $path, $expires)
    {
        
        $allowed = $this->allowedRoutes();
        
        $allowed[$path] = (int) $expires;
        
        $this->put('_allow_routes', $allowed);
    }
    
    private function allowedRoutes() :array
    {
        
        return $this->get('_allow_routes', []);
        
    }
    
    public function canAccessRoute(string $path) :bool
    {
        
        $expires = $this->allowedRoutes()[$path] ?? null;
        
        if ( ! $expires) {
            return false;
        }
        
        if ($expires < $this->currentTime()) {
            
            $this->remove('_allow_routes.'.$path);
            
            return false;
            
        }
        
        return true;
        
    }
    
    public function remove(string $key)
    {
        
        return Arr::pull($this->attributes, $key);
    }
    
    /**
     * @param  DateTimeInterface|DateInterval|int  $delay
     */
    public function setNextRotation($delay)
    {
        
        $ts = $this->availableAt($delay);
        $this->put('_rotate_at', $ts);
        
    }
    
    public function rotationDueAt() :int
    {
        
        return $this->get('_rotate_at', 0);
        
    }
    
    /**
     * @param  DateTimeInterface|DateInterval|int  $delay
     */
    public function setAbsoluteTimeout($delay)
    {
        
        $ts = $this->availableAt($delay);
        $this->put('_expires_at', $ts);
        
    }
    
    public function absoluteTimeout() :int
    {
        
        return $this->get('_expires_at', 0);
        
    }
    
    public function getAllForUser() :array
    {
        
        $sessions = $this->getDriver()->getAllByUserId($this->userId());
        
        $collection = [];
        
        foreach ($sessions as $session) {
            
            $payload = @unserialize($this->prepareForUnserialize($session->payload));
            
            if ($payload !== false && ! is_null($payload) && is_array($payload)) {
                
                $session->payload = $payload;
                
            }
            else {
                
                $session->payload = [];
            }
            
            $collection[] = $session;
        }
        
        return $collection;
        
    }
    
    public function getDriver() :SessionDriver
    {
        
        return $this->driver;
    }
    
    public function userId()
    {
        
        return $this->get('_user.id', 0);
        
    }
    
    public function isIdle(int $idle_timeout) :bool
    {
        
        return ($this->currentTime() - $this->lastActivity()) > $idle_timeout;
    }
    
    public function lastActivity() :int
    {
        
        return $this->get('_last_activity', 0);
        
    }
    
    public function hasRememberMeToken() :bool
    {
        
        return $this->get('auth.has_remember_token', false);
    }
    
    public function challengedUser() :int
    {
        
        return $this->get('auth.2fa.challenged_user', 0);
    }
    
}
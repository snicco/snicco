<?php

declare(strict_types=1);

namespace Snicco\Session;

use Closure;
use DateInterval;
use DateTimeInterface;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Support\Carbon;
use Snicco\Traits\InteractsWithTime;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Middleware\VerifyCsrfToken;

class Session
{
    
    use InteractsWithTime;
    
    private string $id;
    
    private array $attributes = [];
    
    private SessionDriver $driver;
    
    private bool $started = false;
    
    private bool $saved = false;
    
    private array $initial_attributes = [];
    
    private array $loaded_data_from_handler = [];
    
    private int $token_strength_in_bytes;
    
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
        
        if ( ! $this->has(VerifyCsrfToken::TOKEN_KEY)) {
            $this->regenerateCsrfToken();
        }
        
        $this->initial_attributes = $this->attributes;
        
        return $this->started;
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
    
    public function save() :void
    {
        $this->ageFlashData();
        
        $this->setLastActivity($this->currentTime());
        
        $this->driver->write(
            $this->hash($this->getId()),
            $this->prepareForStorage(serialize($this->attributes))
        );
        
        $this->saved = true;
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
    
    public function wasChanged() :bool
    {
        return $this->initial_attributes !== $this->attributes;
    }
    
    public function wasSaved() :bool
    {
        return $this->saved;
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
        $keys = Arr::wrap($key);
        
        foreach ($keys as $key) {
            if ( ! Arr::has($this->attributes, $key)) {
                return false;
            }
        }
        
        return true;
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
        
        $value = $callback();
        $this->put($key, $value);
        return $value;
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
    
    public function has(string $key) :bool
    {
        return Arr::get($this->attributes, $key) !== null;
    }
    
    public function boolean(string $key, bool $default = false) :bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
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
    
    public function keep($keys = null) :void
    {
        $this->mergeNewFlashes(
            $keys = is_array($keys)
                ? $keys
                : func_get_args()
        );
        
        $this->removeFromOldFlashData($keys);
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
        $this->regenerateCsrfToken();
        return $this->migrate();
    }
    
    public function flush() :void
    {
        $this->attributes = [];
    }
    
    public function regenerate(bool $destroy_old = true) :bool
    {
        $success = $this->migrate($destroy_old);
        $this->regenerateCsrfToken();
        return $success;
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
     * @param  array|MessageBag  $provider
     *
     * @return $this
     */
    public function withErrors($provider, string $bag = 'default') :Session
    {
        $value = $this->toMessageBag($provider);
        
        $this->flash('errors', $this->errors()->put($bag, $value));
        
        return $this;
    }
    
    public function errors() :ViewErrors
    {
        $errors = $this->get('errors', new ViewErrors());
        
        if ( ! $errors instanceof ViewErrors) {
            $errors = new ViewErrors;
        }
        
        return $errors;
    }
    
    public function allowAccessToRoute(string $path, $expires)
    {
        $allowed = $this->allowedRoutes();
        
        $allowed[$path] = (int) $expires;
        
        $this->put('_allow_routes', $allowed);
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
    
    public function csrfToken()
    {
        return $this->get(VerifyCsrfToken::TOKEN_KEY);
    }
    
    public function regenerateCsrfToken()
    {
        $this->put(VerifyCsrfToken::TOKEN_KEY, Str::random(40));
    }
    
    protected function prepareForUnserialize(string $data) :string
    {
        return $data;
    }
    
    protected function prepareForStorage(string $data) :string
    {
        return $data;
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
    
    private function ageFlashData() :void
    {
        $this->forget($this->get('_flash.old', []));
        
        $this->put('_flash.old', $this->get('_flash.new', []));
        
        $this->put('_flash.new', []);
    }
    
    private function mergeNewFlashes(array $keys) :void
    {
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));
        
        $this->put('_flash.new', $values);
    }
    
    private function removeFromOldFlashData(array $keys) :void
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
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
    
    private function toMessageBag($provider) :MessageBag
    {
        if ($provider instanceof MessageBag) {
            return $provider;
        }
        
        return new MessageBag($provider);
    }
    
    private function allowedRoutes() :array
    {
        return $this->get('_allow_routes', []);
    }
    
}
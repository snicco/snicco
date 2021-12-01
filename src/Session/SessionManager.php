<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Http\Cookie;
use Snicco\Http\Cookies;
use Snicco\Http\Psr7\Request;
use Snicco\Traits\HasLottery;
use Snicco\Http\ResponseEmitter;
use Snicco\Traits\InteractsWithTime;
use Snicco\Session\Events\SessionWasRegenerated;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Session\Contracts\SessionManagerInterface;

class SessionManager implements SessionManagerInterface
{
    
    use HasLottery;
    use InteractsWithTime;
    
    public const DAY_IN_SEC = 86400;
    public const HOUR_IN_SEC = 3600;
    public const THIRTY_MIN_IN_SEC = 1800;
    public const WEEK_IN_SEC = self::DAY_IN_SEC * 7;
    
    private array $config;
    
    private Session $session;
    
    /**
     * @var callable
     */
    private $absolute_timout_resolver;
    
    private Dispatcher $events;
    
    public function __construct(array $session_config, Session $session, Dispatcher $events)
    {
        $this->config = $session_config;
        $this->session = $session;
        $this->events = $events;
    }
    
    public function save()
    {
        if ($this->session->rotationDueAt() === 0) {
            $this->session->setNextRotation($this->rotationInterval());
        }
        
        if ($this->session->absoluteTimeout() === 0) {
            $this->session->setAbsoluteTimeout($this->maxSessionLifetime());
        }
        
        if ($this->needsRotation()) {
            $this->session->regenerate();
            $this->session->setNextRotation($this->rotationInterval());
            $this->events->dispatch(new SessionWasRegenerated($this->session));
        }
        
        $this->session->save();
    }
    
    public function collectGarbage()
    {
        if ($this->hitsLottery($this->config['lottery'])) {
            $this->session->getDriver()->gc($this->maxSessionLifetime());
        }
    }
    
    public function start(Request $request, int $user_id) :Session
    {
        $cookie_name = $this->config['cookie'];
        $session_id = $request->cookies()->get($cookie_name, '');
        $this->session->start($session_id);
        $this->session->setUserId($user_id);
        
        return $this->session;
    }
    
    public function sessionCookie() :Cookie
    {
        $cookie = new Cookie($this->config['cookie'], $this->session->getId());
        
        $cookie->path($this->config['path'])
               ->sameSite($this->config['same_site'])
               ->expires($this->session->absoluteTimeout())
               ->onlyHttp()
               ->domain($this->config['domain']);
        
        return $cookie;
    }
    
    /**
     * @note We are not in a routing flow. This method gets called on the wp_login/logout
     * event.
     * The Event Listener for this method gets unhooked when using the AUTH-Extension
     *
     * @param  Request  $request
     * @param  ResponseEmitter  $emitter
     */
    public function invalidateAfterLogout(Request $request, ResponseEmitter $emitter)
    {
        $this->start($request, 0);
        
        $this->session->invalidate();
        $this->session->save();
        
        $cookies = new Cookies();
        $cookies->add($this->sessionCookie());
        
        $emitter->emitCookies($cookies);
    }
    
    public function setAbsoluteTimeoutResolver(callable $resolver)
    {
        $this->absolute_timout_resolver = $resolver;
    }
    
    private function rotationInterval() :int
    {
        return $this->config['rotate'];
    }
    
    private function maxSessionLifetime()
    {
        $timeout = $this->config['lifetime'];
        
        if (isset($this->absolute_timout_resolver)
            && is_callable(
                $this->absolute_timout_resolver
            )) {
            return call_user_func($this->absolute_timout_resolver, $timeout);
        }
        
        return $timeout;
    }
    
    private function needsRotation() :bool
    {
        if ( ! isset($this->config['rotate']) || ! is_int($this->config['rotate'])) {
            return false;
        }
        
        $rotation = $this->session->rotationDueAt();
        
        return $this->currentTime() - $rotation > 0;
    }
    
}
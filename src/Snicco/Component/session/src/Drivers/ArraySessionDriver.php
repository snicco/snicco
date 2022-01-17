<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use DateTimeImmutable;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\ValueObjects\SerializedSessionData;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;

use function function_exists;
use function get_current_user_id;

/**
 * @api
 * @todo Cleanup uncommented code
 */
final class ArraySessionDriver implements SessionDriver
{
    
    /**
     * @var array
     */
    private $storage = [];
    
    /**
     * @var SessionClock
     */
    private $clock;
    
    public function __construct(SessionClock $clock = null)
    {
        $this->clock = $clock ?? new ClockUsingDateTimeImmutable();
    }
    
    public function destroy(array $session_ids) :void
    {
        foreach ($session_ids as $session_id) {
            if (isset($this->storage[$session_id])) {
                unset($this->storage[$session_id]);
            }
        }
    }
    
    public function gc(int $seconds_without_activity) :void
    {
        $expiration = $this->calculateExpiration($seconds_without_activity);
        
        foreach ($this->storage as $sessionId => $session) {
            if ($session['last_activity'] < $expiration) {
                unset($this->storage[$sessionId]);
            }
        }
    }
    
    public function read(string $session_id) :SerializedSessionData
    {
        if ( ! isset($this->storage[$session_id])) {
            throw BadSessionID::forID($session_id, 'array');
        }
        return SerializedSessionData::fromSerializedString(
            $this->storage[$session_id]['data'],
            $this->storage[$session_id]['last_activity'],
        );
    }
    
    public function touch(string $session_id, DateTimeImmutable $now) :void
    {
        if ( ! isset($this->storage[$session_id])) {
            throw BadSessionID::forId($session_id, 'array');
        }
        
        $this->storage[$session_id]['last_activity'] = $now->getTimestamp();
    }
    
    public function write(string $session_id, SerializedSessionData $data) :void
    {
        $this->storage[$session_id] = [
            'data' => $data->asString(),
            'last_activity' => $data->lastActivity()->getTimestamp(),
            'user_id' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
        ];
    }
    
    public function all() :array
    {
        return $this->storage;
    }
    
    //public function getAllByUserId(int $user_id) :SessionInfos
    //{
    //    $user_sessions = [];
    //
    //    foreach ($this->storage as $id => $session_record) {
    //        if ($session_record['user_id'] === $user_id) {
    //            $session = new SessionInfo($id, $session_record['data']);
    //            $user_sessions[] = $session;
    //        }
    //    }
    //    return new SessionInfos($user_sessions);
    //}
    
    //public function destroyOthersForUser(string $hashed_token, int $user_id) :void
    //{
    //    foreach ($this->storage as $id => $session) {
    //        if ($session['user_id'] !== $user_id) {
    //            continue;
    //        }
    //
    //        if ($id === $hashed_token) {
    //            continue;
    //        }
    //
    //        unset($this->storage[$id]);
    //    }
    //}
    
    //public function destroyAllForUser(int $user_id) :void
    //{
    //    foreach ($this->storage as $id => $session) {
    //        if ($session['user_id'] !== $user_id) {
    //            continue;
    //        }
    //
    //        unset($this->storage[$id]);
    //    }
    //}
    
    //public function destroyAll() :void
    //{
    //    $this->storage = [];
    //}
    
    private function calculateExpiration(int $seconds) :int
    {
        return $this->clock->currentTimestamp() - $seconds;
    }
    
}
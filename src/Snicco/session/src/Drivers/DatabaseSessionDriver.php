<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use wpdb;
use stdClass;
use Throwable;
use RuntimeException;
use DateTimeImmutable;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\Exceptions\CantDestroySession;
use Snicco\Session\Exceptions\CantWriteSessionContent;
use Snicco\Session\ValueObjects\SerializedSessionData;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;

use function rtrim;
use function count;
use function is_object;
use function filter_var;
use function str_repeat;
use function array_merge;
use function base64_encode;
use function base64_decode;
use function get_current_user_id;

/**
 * @api
 * @todo Cleanup uncommented code
 */
final class DatabaseSessionDriver implements SessionDriver
{
    
    /**
     * @var wpdb
     */
    private $db;
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var SessionClock
     */
    private $clock;
    
    public function __construct(wpdb $db, string $table, SessionClock $clock = null)
    {
        $this->db = $db;
        $this->table = $this->db->prefix.$table;
        $this->clock = $clock ?? new ClockUsingDateTimeImmutable();
    }
    
    public function destroy(array $session_ids) :void
    {
        $count = count($session_ids);
        
        if ($count < 1) {
            return;
        }
        
        $placeholders = rtrim(str_repeat('%s,', $count), ',');
        $q = "DELETE FROM $this->table WHERE id in ($placeholders)";
        
        $success = $this->db->query(
            $this->db->prepare($q, $session_ids)
        );
        
        if ($success === false) {
            throw CantDestroySession::forSessionIDs($session_ids, 'database');
        }
    }
    
    public function gc(int $seconds_without_activity) :void
    {
        $must_be_newer_than = $this->currentTime() - $seconds_without_activity;
        
        $query = $this->db->prepare(
            "DELETE FROM $this->table WHERE last_activity < %d",
            $must_be_newer_than
        );
        
        $this->db->query($query);
    }
    
    public function read(string $session_id) :SerializedSessionData
    {
        $session = $this->findSession($session_id);
        
        if ( ! isset($session->data)) {
            throw BadSessionID::forID($session_id, 'database');
        }
        
        return SerializedSessionData::fromSerializedString(
            base64_decode($session->data),
            (int) $session->last_activity,
        );
    }
    
    public function touch(string $session_id, DateTimeImmutable $now) :void
    {
        $query = "UPDATE $this->table SET last_activity = %d where id = %s";
        $query = $this->db->prepare($query, $now->getTimestamp(), $session_id);
        
        $res = $this->db->query($query);
        
        if ($res === false) {
            throw CantWriteSessionContent::forId(
                $session_id,
                'database',
                new RuntimeException($this->db->last_error)
            );
        }
        
        if ($res < 1) {
            throw BadSessionID::forId($session_id, 'database');
        }
    }
    
    public function write(string $session_id, SerializedSessionData $data) :void
    {
        try {
            if ($this->exists($session_id)) {
                $this->performUpdate($session_id, $data);
            }
            else {
                $this->performInsert($session_id, $data);
            }
        } catch (RuntimeException $e) {
            throw CantWriteSessionContent::forId($session_id, 'database', $e);
        }
    }
    
    private function findSession(string $id) :object
    {
        $query = $this->db->prepare("SELECT * FROM `$this->table` WHERE `id` = %s", $id);
        
        $val = $this->db->get_row($query, OBJECT);
        return is_object($val) ? $val : new stdClass();
    }
    
    /**
     * @throws RuntimeException
     */
    private function performUpdate(string $id, SerializedSessionData $data) :void
    {
        try {
            $data = array_merge($this->getPayloadData($id, $data), [$id]);
            
            $query = $this->db->prepare(
                "UPDATE `$this->table` SET id = %s, user_id = %d, data = %s, last_activity = %d WHERE id= %s",
                $data
            );
            
            $success = ($this->db->query($query) !== false);
        } catch (Throwable $e) {
            throw new RuntimeException($this->db->last_error, $e->getCode(), $e);
        }
        
        if ( ! $success) {
            throw new RuntimeException($this->db->last_error);
        }
    }
    
    /**
     * @throws RuntimeException
     */
    private function performInsert(string $session_id, SerializedSessionData $payload) :void
    {
        try {
            $data = $this->getPayloadData($session_id, $payload);
            
            $query = $this->db->prepare(
                "INSERT INTO `$this->table` (`id`,`user_id`, `data`, `last_activity`) VALUES(%s, %d, %s, %d)",
                $data
            );
            
            $success = ($this->db->query($query) !== false);
        } catch (Throwable $e) {
            throw new RuntimeException($this->db->last_error, $e->getCode(), $e);
        }
        
        if ( ! $success) {
            throw new RuntimeException($this->db->last_error);
        }
    }
    
    private function getPayloadData(string $session_id, SerializedSessionData $data) :array
    {
        return [
            'id' => $session_id,
            'user_id' => get_current_user_id(),
            'data' => base64_encode($data->asString()),
            'last_activity' => $data->lastActivity()->getTimestamp(),
        ];
    }
    
    private function exists(string $session_id) :bool
    {
        $query = $this->db->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE id = %s)",
            $session_id,
        );
        
        $exists = $this->db->get_var($query);
        
        return filter_var($exists, FILTER_VALIDATE_BOOLEAN);
    }
    
    private function currentTime() :int
    {
        return $this->clock->currentTimestamp();
    }
    
    //public function getAllByUserId(int $user_id) :array
    //{
    //    $query = $this->db->prepare("SELECT * FROM `$this->table` WHERE `user_id` = %d", $user_id);
    //
    //    $sessions = $this->db->get_results($query, OBJECT_K) ?? [];
    //
    //    $sessions = array_map(function (object $session) {
    //        if ( ! $session->data) {
    //            return null;
    //        }
    //        $id = SessionId::fromCookieId($session->id);
    //        $data = SerializedSessionData::fromSerializedString(base64_decode($session->data));
    //        return new Session($id, $data->asArray());
    //    }, $sessions);
    //
    //    return array_filter($sessions);
    //}
    //
    //public function destroyOthersForUser(string $hashed_token, int $user_id) :void
    //{
    //    $query = $this->db->prepare(
    //        "DELETE FROM $this->table WHERE user_id = %d AND NOT `id` = %s",
    //        $user_id,
    //        $hashed_token
    //    );
    //
    //    $this->db->query($query);
    //}
    //
    //public function destroyAllForUser(int $user_id) :void
    //{
    //    $query = $this->db->prepare("DELETE FROM $this->table WHERE user_id = %d", $user_id);
    //
    //    $this->db->query($query);
    //}
    //
    //public function destroyAll() :void
    //{
    //    $this->db->query("TRUNCATE TABLE $this->table");
    //}
    
}
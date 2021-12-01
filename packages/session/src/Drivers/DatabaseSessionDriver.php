<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use wpdb;
use Snicco\Support\WP;
use Snicco\Support\Carbon;
use Snicco\Http\Psr7\Request;
use Snicco\Traits\InteractsWithTime;
use Snicco\Session\Contracts\SessionDriver;

class DatabaseSessionDriver implements SessionDriver
{
    
    use InteractsWithTime;
    
    private wpdb    $db;
    private int     $absolute_lifetime_in_seconds;
    private Request $request;
    private string  $table;
    private object  $session;
    
    public function __construct(wpdb $db, string $table, int $lifetime_in_sec)
    {
        $this->db = $db;
        $this->table = $this->db->prefix.$table;
        $this->absolute_lifetime_in_seconds = $lifetime_in_sec;
    }
    
    public function close() :bool
    {
        return true;
    }
    
    public function destroy($hased_id) :bool
    {
        $result = $this->db->delete($this->table, ['id' => $hased_id], ['%s']);
        
        return $result !== false;
    }
    
    public function gc($max_lifetime) :bool
    {
        $must_be_newer_than = $this->currentTime() - $max_lifetime;
        
        $query = $this->db->prepare(
            "DELETE FROM $this->table WHERE last_activity <= %d",
            $must_be_newer_than
        );
        
        return $this->db->query($query) !== false;
    }
    
    public function open($path, $name) :bool
    {
        return true;
    }
    
    public function read($hashed_id)
    {
        $session = $this->findSession($hashed_id);
        
        if ( ! isset($session->payload) || $this->isExpired($session)) {
            return '';
        }
        
        return base64_decode($session->payload);
    }
    
    public function write($hashed_id, $data) :bool
    {
        if ($this->exists($hashed_id)) {
            return $this->performUpdate($hashed_id, $data);
        }
        
        return $this->performInsert($hashed_id, $data);
    }
    
    public function isValid(string $hashed_id) :bool
    {
        return $this->hasSessionId($hashed_id);
    }
    
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    public function getAllByUserId(int $user_id) :array
    {
        $query = $this->db->prepare("SELECT * FROM `$this->table` WHERE `user_id` = %d", $user_id);
        
        $sessions = $this->db->get_results($query, OBJECT) ?? [];
        
        $sessions = array_map(function (object $session) {
            if ( ! $session->payload) {
                return null;
            }
            $session->payload = base64_decode($session->payload);
            return $session;
        }, $sessions);
        
        return array_filter($sessions);
    }
    
    public function destroyOthersForUser(string $hashed_token, int $user_id)
    {
        $query = $this->db->prepare(
            "DELETE FROM $this->table WHERE user_id = %d AND NOT `id` = %s",
            $user_id,
            $hashed_token
        );
        
        $this->db->query($query);
    }
    
    public function destroyAllForUser(int $user_id)
    {
        $query = $this->db->prepare("DELETE FROM $this->table WHERE user_id = %d", $user_id);
        
        $this->db->query($query);
    }
    
    public function destroyAll()
    {
        $this->db->query("TRUNCATE TABLE $this->table");
    }
    
    private function findSession(string $id) :object
    {
        $query = $this->db->prepare("SELECT * FROM `$this->table` WHERE `id` = %s", $id);
        
        return (object) $this->db->get_row($query, ARRAY_A);
    }
    
    private function isExpired(object $session) :bool
    {
        return isset($session->last_activity)
               && $session->last_activity < Carbon::now()->subSeconds(
                $this->absolute_lifetime_in_seconds
            )
                                                  ->getTimestamp();
    }
    
    private function exists(string $session_id) :bool
    {
        $query = $this->db->prepare(
            "SELECT `id` FROM `$this->table` WHERE `id` = %s",
            $session_id
        );
        
        return $this->db->get_var($query) !== null;
    }
    
    private function performUpdate(string $id, string $payload) :bool
    {
        $data = array_merge($this->getPayloadData($id, $payload), [$id]);
        
        $query = $this->db->prepare(
            "UPDATE `$this->table`
SET
    id=%s, user_id=%d, ip_address=%s , user_agent=%s , payload = %s, last_activity = %d
WHERE
      id=%s",
            $data
        );
        
        return $this->db->query($query) !== false;
    }
    
    private function getPayloadData(string $session_id, string $payload) :array
    {
        return [
            'id' => $session_id,
            'user_id' => WP::userId(),
            'ip_address' => isset($this->request) ? $this->request->getAttribute('ip_address', '')
                : '',
            'user_agent' => $this->userAgent(),
            'payload' => base64_encode($payload),
            'last_activity' => $this->currentTime(),
        ];
    }
    
    private function userAgent() :string
    {
        if ( ! isset($this->request)) {
            return '';
        }
        
        return substr($this->request->getHeaderLine('User-Agent'), 0, 500);
    }
    
    private function performInsert(string $session_id, string $payload) :bool
    {
        $data = $this->getPayloadData($session_id, $payload);
        
        $query = $this->db->prepare(
            "INSERT INTO `$this->table` (`id`,`user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES(%s, %d, %s, %s, %s, %d)",
            $data
        );
        
        return $this->db->query($query) !== false;
    }
    
    private function hasSessionId(string $id) :bool
    {
        $must_be_newer_than = Carbon::now()->subSeconds($this->absolute_lifetime_in_seconds)
                                    ->getTimestamp();
        
        $query = $this->db->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE id = %s AND last_activity > %d LIMIT 1)",
            $id,
            $must_be_newer_than
        );
        
        $exists = $this->db->get_var($query);
        
        return (is_string($exists) && $exists === '1');
    }
    
}
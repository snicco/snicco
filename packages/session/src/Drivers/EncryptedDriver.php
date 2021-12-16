<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use DateTimeImmutable;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Contracts\SessionEncryptor;
use Snicco\Session\ValueObjects\SerializedSessionData;

/**
 * @api
 * @todo Cleanup uncommented code
 */
final class EncryptedDriver implements SessionDriver
{
    
    /**
     * @var SessionDriver
     */
    private $driver;
    
    /**
     * @var SessionEncryptor
     */
    private $encryptor;
    
    public function __construct(SessionDriver $driver, SessionEncryptor $encryptor)
    {
        $this->driver = $driver;
        $this->encryptor = $encryptor;
    }
    
    public function destroy(array $session_ids) :void
    {
        $this->driver->destroy($session_ids);
    }
    
    public function gc(int $seconds_without_activity) :void
    {
        $this->driver->gc($seconds_without_activity);
    }
    
    public function read(string $session_id) :SerializedSessionData
    {
        $data = $this->driver->read($session_id);
        
        return SerializedSessionData::fromSerializedString(
            $this->encryptor->decrypt(
                $data->asArray()['encrypted_session_data'],
            ),
            $data->lastActivity()->getTimestamp()
        );
    }
    
    public function touch(string $session_id, DateTimeImmutable $now) :void
    {
        $this->driver->touch($session_id, $now);
    }
    
    public function write(string $session_id, SerializedSessionData $data) :void
    {
        $as_string = $data->asString();
        $encrypted = $this->encryptor->encrypt($as_string);
        
        $this->driver->write(
            $session_id,
            SerializedSessionData::fromArray(
                ['encrypted_session_data' => $encrypted],
                $data->lastActivity()->getTimestamp()
            )
        );
    }
    
    //public function getAllByUserId(int $user_id) :array
    //{
    //    $sessions = $this->driver->getAllByUserId($user_id);
    //
    //    if (empty($sessions)) {
    //        return $sessions;
    //    }
    //
    //    return array_map(function (object $session) {
    //        $session->data = $this->encryptor->decrypt($session->data);
    //        return $session;
    //    }, $sessions);
    //}
    //
    //public function destroyOthersForUser(string $hashed_token, int $user_id) :void
    //{
    //    $this->driver->destroyOthersForUser($hashed_token, $user_id);
    //}
    //
    //public function destroyAllForUser(int $user_id) :void
    //{
    //    $this->driver->destroyAllForUser($user_id);
    //}
    //
    //public function destroyAll() :void
    //{
    //    $this->driver->destroyAll();
    //}
    
}
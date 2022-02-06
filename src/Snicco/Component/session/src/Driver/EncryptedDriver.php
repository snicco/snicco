<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use DateTimeImmutable;
use RuntimeException;
use Snicco\Component\Session\SessionEncryptor;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

use function is_string;

/**
 * @api
 */
final class EncryptedDriver implements SessionDriver
{

    private SessionDriver $driver;
    private SessionEncryptor $encryptor;

    public function __construct(SessionDriver $driver, SessionEncryptor $encryptor)
    {
        $this->driver = $driver;
        $this->encryptor = $encryptor;
    }

    public function destroy(array $session_ids): void
    {
        $this->driver->destroy($session_ids);
    }

    public function gc(int $seconds_without_activity): void
    {
        $this->driver->gc($seconds_without_activity);
    }

    public function read(string $session_id): SerializedSessionData
    {
        $data = $this->driver->read($session_id);

        $arr = $data->asArray();
        if (!isset($arr['encrypted_session_data']) || !is_string($arr['encrypted_session_data'])) {
            throw new RuntimeException('The session data is corrupted. Does not contain key [encrypted_session_data].');
        }

        return SerializedSessionData::fromSerializedString(
            $this->encryptor->decrypt(
                $arr['encrypted_session_data'],
            ),
            $data->lastActivity()->getTimestamp()
        );
    }

    public function touch(string $session_id, DateTimeImmutable $now): void
    {
        $this->driver->touch($session_id, $now);
    }

    public function write(string $session_id, SerializedSessionData $data): void
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

}
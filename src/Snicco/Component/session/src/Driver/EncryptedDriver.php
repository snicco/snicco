<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use BadMethodCallException;
use Snicco\Component\Session\SessionEncryptor;
use Snicco\Component\Session\ValueObject\SerializedSession;

final class EncryptedDriver implements UserSessionsDriver
{
    private SessionDriver $driver;

    private SessionEncryptor $encryptor;

    public function __construct(SessionDriver $driver, SessionEncryptor $encryptor)
    {
        $this->driver = $driver;
        $this->encryptor = $encryptor;
    }

    public function destroy(array $selectors): void
    {
        $this->driver->destroy($selectors);
    }

    public function gc(int $seconds_without_activity): void
    {
        $this->driver->gc($seconds_without_activity);
    }

    public function read(string $selector): SerializedSession
    {
        $encrypted_session = $this->driver->read($selector);

        return SerializedSession::fromString(
            $this->encryptor->decrypt($encrypted_session->data()),
            $encrypted_session->hashedValidator(),
            $encrypted_session->lastActivity(),
            $encrypted_session->userId()
        );
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        $this->driver->touch($selector, $current_timestamp);
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $data = $session->data();
        $encrypted_data = $this->encryptor->encrypt($data);

        $this->driver->write(
            $selector,
            SerializedSession::fromString(
                $encrypted_data,
                $session->hashedValidator(),
                $session->lastActivity(),
                $session->userId()
            )
        );
    }

    public function destroyAll(): void
    {
        if (! $this->driver instanceof UserSessionsDriver) {
            throw new BadMethodCallException(__METHOD__ . ' needs an implementation of ' . UserSessionsDriver::class);
        }
        $this->driver->destroyAll();
    }

    public function destroyAllForUserId($user_id): void
    {
        if (! $this->driver instanceof UserSessionsDriver) {
            throw new BadMethodCallException(__METHOD__ . ' needs an implementation of ' . UserSessionsDriver::class);
        }
        $this->driver->destroyAllForUserId($user_id);
    }

    public function destroyAllForUserIdExcept(string $selector, $user_id): void
    {
        if (! $this->driver instanceof UserSessionsDriver) {
            throw new BadMethodCallException(__METHOD__ . ' needs an implementation of ' . UserSessionsDriver::class);
        }
        $this->driver->destroyAllForUserIdExcept($selector, $user_id);
    }

    public function getAllForUserId($user_id): iterable
    {
        if (! $this->driver instanceof UserSessionsDriver) {
            throw new BadMethodCallException(__METHOD__ . ' needs an implementation of ' . UserSessionsDriver::class);
        }
        return $this->driver->getAllForUserId($user_id);
    }
}

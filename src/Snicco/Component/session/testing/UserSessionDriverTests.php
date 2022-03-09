<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Testing;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\ValueObject\SerializedSession;

use function array_key_first;
use function array_keys;
use function array_merge;
use function array_shift;
use function hash;
use function time;

/**
 * @codeCoverageIgnore
 */
trait UserSessionDriverTests
{
    /**
     * @test
     */
    public function all_sessions_for_all_users_can_be_destroyed(): void
    {
        $sessions = $this->createSessions(5, [1, 2, 3, 'string-uuid', 'string-uuid']);

        $user_sessions = $this->createUserSessionDriver($sessions);

        $user_sessions->write(
            'session_no_user1',
            SerializedSession::fromString('foo', 'val1', time())
        );
        $user_sessions->write(
            'session_no_user2',
            SerializedSession::fromString('foo', 'val1', time())
        );

        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(1));
        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(2));
        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(3));
        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(3));
        PHPUnit::assertCount(2, $user_sessions->getAllForUserId('string-uuid'));

        $user_sessions->destroyAll();

        foreach (['session_no_user1', 'session_no_user2'] as $selector) {
            try {
                $user_sessions->read($selector);
                PHPUnit::fail("User session [$selector] was not deleted.");
            } catch (BadSessionID $e) {
                PHPUnit::assertStringContainsString($selector, $e->getMessage());
            }
        }

        foreach (array_keys($sessions) as $selector) {
            try {
                $user_sessions->read($selector);
                PHPUnit::fail("User session [$selector] was not deleted.");
            } catch (BadSessionID $e) {
                PHPUnit::assertStringContainsString($selector, $e->getMessage());
            }
        }

        PHPUnit::assertCount(0, $user_sessions->getAllForUserId(1));
        PHPUnit::assertCount(0, $user_sessions->getAllForUserId(2));
        PHPUnit::assertCount(0, $user_sessions->getAllForUserId(3));
        PHPUnit::assertCount(0, $user_sessions->getAllForUserId('string-uuid'));
    }

    /**
     * @test
     */
    public function all_sessions_for_a_specific_user_can_be_destroyed(): void
    {
        $user_one_sessions = $this->createSessions(1, [1]);
        $user_two_sessions = $this->createSessions(4, [2, 2, 2, 2]);

        $user_sessions = $this->createUserSessionDriver(array_merge($user_one_sessions, $user_two_sessions));

        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(1));
        PHPUnit::assertCount(4, $user_sessions->getAllForUserId(2));

        $user_sessions->destroyAllForUserId(2);

        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(1));
        PHPUnit::assertCount(0, $user_sessions->getAllForUserId(2));

        foreach (array_keys($user_two_sessions) as $selector) {
            try {
                $user_sessions->read($selector);
                PHPUnit::fail("User session [$selector] was not deleted.");
            } catch (BadSessionID $e) {
                PHPUnit::assertStringContainsString($selector, $e->getMessage());
            }
        }

        foreach (array_keys($user_one_sessions) as $selector) {
            $user_sessions->read($selector);
        }
    }

    /**
     * @test
     */
    public function all_sessions_for_a_user_can_be_retrieved(): void
    {
        $user_one_sessions = $this->createSessions(1, [1]);
        $user_two_sessions = $this->createSessions(4, [2, 2, 2, 2]);

        $user_sessions = $this->createUserSessionDriver(array_merge($user_one_sessions, $user_two_sessions));

        PHPUnit::assertCount(1, $user_sessions->getAllForUserId(1));
        PHPUnit::assertCount(4, $user_sessions->getAllForUserId(2));

        $user_one_stored = $user_sessions->getAllForUserId(1);
        PHPUnit::assertEquals($user_one_sessions, $user_one_stored);

        $user_two_stored = $user_sessions->getAllForUserId(2);
        PHPUnit::assertEquals($user_two_sessions, $user_two_stored);

        PHPUnit::assertEquals([], $user_sessions->getAllForUserId(10));
        PHPUnit::assertEquals([], $user_sessions->getAllForUserId('bogus'));
    }

    /**
     * @test
     */
    public function all_sessions_for_a_user_expect_one_can_be_destroyed(): void
    {
        $user_one_sessions = $this->createSessions(1, [1]);
        $user_two_sessions = $this->createSessions(4, [2, 2, 2, 2]);

        $user_session_driver = $this->createUserSessionDriver(array_merge($user_one_sessions, $user_two_sessions));

        PHPUnit::assertCount(1, $user_session_driver->getAllForUserId(1));
        PHPUnit::assertCount(4, $user_session_driver->getAllForUserId(2));

        // nothing destroyed
        /** @psalm-suppress PossiblyNullArgument */
        $user_session_driver->destroyAllForUserIdExcept(array_key_first($user_two_sessions), 3);

        $user_one_stored = $user_session_driver->getAllForUserId(1);
        PHPUnit::assertEquals($user_one_sessions, $user_one_stored);

        $user_two_stored = $user_session_driver->getAllForUserId(2);
        PHPUnit::assertEquals($user_two_sessions, $user_two_stored);

        /** @psalm-suppress PossiblyNullArgument */
        $user_session_driver->destroyAllForUserIdExcept(array_key_first($user_two_sessions), 2);

        // user one still the same
        $user_one_stored = $user_session_driver->getAllForUserId(1);
        PHPUnit::assertEquals($user_one_sessions, $user_one_stored);

        $user_two_stored = $user_session_driver->getAllForUserId(2);
        PHPUnit::assertCount(1, $user_two_stored);

        foreach (array_keys($user_two_sessions) as $selector) {
            if ($selector === array_key_first($user_two_sessions)) {
                $user_session_driver->read($selector);
                continue;
            }

            try {
                $user_session_driver->read($selector);
                PHPUnit::fail("User session [$selector] was not deleted.");
            } catch (BadSessionID $e) {
                PHPUnit::assertStringContainsString($selector, $e->getMessage());
            }
        }
    }

    /**
     * @param array<string,SerializedSession> $user_sessions
     */
    abstract protected function createUserSessionDriver(array $user_sessions): UserSessionsDriver;

    /**
     * @param array<int|string> $ids
     *
     * @return array<string,SerializedSession>
     *
     * @throws JsonException
     */
    private function createSessions(int $count, array $ids): array
    {
        if ($count !== count($ids)) {
            throw new InvalidArgumentException('$count must match the count of $ids.');
        }

        $serializer = new JsonSerializer();

        $_serialized = [];
        for ($i = 0; $i < $count; $i++) {
            $session = ReadWriteSession::createEmpty(time());
            $id = array_shift($ids);

            if (null === $id) {
                throw new InvalidArgumentException('Id cant be null.');
            }

            $session->setUserId($id);

            /** @var string $hash */
            $hash = hash('sha256', $session->id()->validator());

            $_serialized[$session->id()->selector()] = SerializedSession::fromString(
                $serializer->serialize($session->all()),
                $hash,
                time(),
                $id
            );
        }
        return $_serialized;
    }
}

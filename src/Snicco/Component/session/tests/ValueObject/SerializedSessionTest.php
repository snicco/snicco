<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SerializedSession;

use function serialize;
use function time;

final class SerializedSessionTest extends TestCase
{
    /**
     * @test
     */
    public function test_fromString(): void
    {
        $serialized_session = SerializedSession::fromString(
            $as_string = serialize([
                'foo' => 'bar',
            ]),
            'foobar',
            time()
        );

        $this->assertSame($as_string, $serialized_session->data());
    }


    /**
     * @test
     */
    public function test_lastActivity(): void
    {
        $data = SerializedSession::fromString(
            'foo',
            'foobar',
            time()
        );

        $this->assertSame(time(), $data->lastActivity());
    }

    /**
     * @test
     */
    public function test_hashed_validator(): void
    {
        $data = SerializedSession::fromString(
            'foo',
            'foobar',
            time()
        );

        $this->assertSame('foobar', $data->hashedValidator());
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_userId(): void
    {
        $session = SerializedSession::fromString(
            'foo',
            'foobar',
            time(),
        );

        $this->assertSame(null, $session->userId());

        $session = SerializedSession::fromString(
            'foo',
            'foobar',
            time(),
            1
        );

        $this->assertSame(1, $session->userId());

        $session = SerializedSession::fromString(
            'foo',
            'foobar',
            time(),
            'user_id'
        );

        $this->assertSame('user_id', $session->userId());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('null, string or integer');

        SerializedSession::fromString(
            'foo',
            'foobar',
            time(),
            true
        );
    }
}

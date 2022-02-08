<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

use function serialize;
use function time;

final class SerializedSessionDataTest extends TestCase
{

    /**
     * @test
     */
    public function testFromSerializedString(): void
    {
        $data = SerializedSessionData::fromSerializedString(
            $as_string = serialize(['foo' => 'bar']),
            time()
        );

        $this->assertSame($as_string, $data->asString());
        $this->assertSame(['foo' => 'bar'], $data->asArray());
    }

    /**
     * @test
     */
    public function test_exception_for_bad_serialized_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('serialized string');
        SerializedSessionData::fromSerializedString('foo', time());
    }

    /**
     * @test
     */
    public function testFromArray(): void
    {
        $data = SerializedSessionData::fromArray(
            $array = ['foo' => 'bar'],
            time()
        );

        $this->assertSame(serialize($array), $data->asString());
        $this->assertSame($array, $data->asArray());
    }

    /**
     * @test
     */
    public function testLastActivity(): void
    {
        $data = SerializedSessionData::fromArray(
            ['foo' => 'bar'],
            time()
        );

        $this->assertSame(time(), $data->lastActivity()->getTimestamp());
    }

    /**
     * @test
     */
    public function test_returns_empty_array_if_serialized_string_is_not_array(): void
    {
        $data = SerializedSessionData::fromSerializedString(
            serialize('foo'),
            time()
        );
        $this->assertSame([], $data->asArray());
    }

}
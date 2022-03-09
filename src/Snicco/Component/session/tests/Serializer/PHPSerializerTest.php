<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Session\Serializer\PHPSerializer;

use function serialize;

/**
 * @internal
 */
final class PHPSerializerTest extends TestCase
{
    /**
     * @test
     */
    public function test_serialize_deserialize(): void
    {
        $s = new PHPSerializer();
        $data = $s->serialize([
            'foo' => 'bar',
        ]);
        $this->assertSame(serialize([
            'foo' => 'bar',
        ]), $data);

        $this->assertSame([
            'foo' => 'bar',
        ], $s->deserialize($data));
    }

    /**
     * @test
     */
    public function test_exception_for_deserialize(): void
    {
        $this->expectException(RuntimeException::class);
        $s = new PHPSerializer();
        $s->deserialize('foo');
    }

    /**
     * @test
     */
    public function test_exception_for_deserialize_on_non_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('array');
        $s = new PHPSerializer();
        $s->deserialize(serialize('foo'));
    }
}

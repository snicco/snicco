<?php

declare(strict_types=1);


namespace Snicco\Component\Session\Tests\Serializer;

use JsonException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Serializer\JsonSerializer;

use function json_encode;

final class JsonSerializerTest extends TestCase
{

    /**
     * @test
     */
    public function test_serialize_deserialize(): void
    {
        $s = new JsonSerializer();
        $data = $s->serialize(['foo' => 'bar']);
        $this->assertSame(json_encode(['foo' => 'bar']), $data);

        $this->assertSame(['foo' => 'bar'], $s->deserialize($data));
    }

    /**
     * @test
     */
    public function test_exception_for_serialize(): void
    {
        $this->expectException(JsonException::class);
        $s = new JsonSerializer();
        $s->serialize(["\xB1\x31" => 'bar']);
    }

    /**
     * @test
     */
    public function test_exception_for_deserialize(): void
    {
        $this->expectException(JsonException::class);
        $s = new JsonSerializer();
        $s->deserialize("\xB1\x31");
    }

}
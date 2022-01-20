<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\unit\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

final class SerializedSessionDataTest extends TestCase
{
    
    /** @test */
    public function testFromSerializedString()
    {
        $data = SerializedSessionData::fromSerializedString(
            $as_string = serialize(['foo' => 'bar']),
            time()
        );
        
        $this->assertSame($as_string, $data->asString());
        $this->assertSame(['foo' => 'bar'], $data->asArray());
    }
    
    /** @test */
    public function testFromArray()
    {
        $data = SerializedSessionData::fromArray(
            $array = ['foo' => 'bar'],
            time()
        );
        
        $this->assertSame(serialize($array), $data->asString());
        $this->assertSame($array, $data->asArray());
    }
    
    /** @test */
    public function testLastActivity()
    {
        $data = SerializedSessionData::fromArray(
            ['foo' => 'bar'],
            time()
        );
        
        $this->assertSame(time(), $data->lastActivity()->getTimestamp());
    }
    
}
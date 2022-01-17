<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use Tests\Codeception\shared\UnitTest;
use Snicco\Session\ValueObjects\SerializedSessionData;

final class SerializedSessionDataTest extends UnitTest
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
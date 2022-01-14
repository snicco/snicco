<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Snicco\Core\Http\ResponseFactory;
use Tests\Codeception\shared\UnitTest;
use Tests\Codeception\shared\helpers\CreateUrlGenerator;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

final class DelegatedResponseTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreateUrlGenerator;
    
    private ResponseFactory $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory($this->createUrlGenerator());
    }
    
    /** @test */
    public function test_sendHeaders()
    {
        $response = $this->factory->delegate();
        $this->assertTrue($response->shouldHeadersBeSent());
        
        $response = $this->factory->delegate(false);
        $this->assertFalse($response->shouldHeadersBeSent());
    }
    
}
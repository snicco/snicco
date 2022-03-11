<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

/**
 * @internal
 */
final class DelegatedResponseTest extends TestCase
{
    use CreateTestPsr17Factories;

    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
    }

    /**
     * @test
     */
    public function test_send_headers(): void
    {
        $response = $this->factory->delegate();
        $this->assertTrue($response->shouldHeadersBeSent());

        $new = $response->withoutSendingHeaders();
        $this->assertFalse($new->shouldHeadersBeSent());

        // immutable
        $this->assertTrue($response->shouldHeadersBeSent());
    }
}

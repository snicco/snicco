<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\Secret;

final class SecretTest extends TestCase
{
    /**
     * @test
     */
    public function test_error_for_weak_strength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Secret::generate(15);
    }

    /**
     * @test
     */
    public function a_secret_can_be_generated_at_min_strength_16(): void
    {
        $secret = Secret::generate(16);
        $this->assertInstanceOf(Secret::class, $secret);
    }

    /**
     * @test
     */
    public function test_fromHexEncoded(): void
    {
        $secret = Secret::generate();

        // store $secret
        $stored = $secret->asString();

        $secret_new = Secret::fromHexEncoded($stored);
        $this->assertSame($secret->asString(), $secret_new->asString());
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_for_a_clearly_malformed_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Secret::fromHexEncoded('badsecret');
    }

    /**
     * @test
     */
    public function binary_secrets_stay_the_same(): void
    {
        $secret = Secret::generate();

        $bytes = $secret->asBytes();

        // store $secret
        $stored = $secret->asString();

        $secret_new = Secret::fromHexEncoded($stored);
        $this->assertSame($secret->asString(), $secret_new->asString());
        $this->assertSame($bytes, $secret_new->asBytes());
    }
}

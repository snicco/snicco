<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlGenerator;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;

/**
 * @internal
 */
final class UrlGenerationContextTest extends TestCase
{
    /**
     * @test
     */
    public function test_properties(): void
    {
        $context = new UrlGenerationContext('foobar.com');

        $this->assertSame('foobar.com', $context->host());
        $this->assertSame(80, $context->httpPort());
        $this->assertSame(443, $context->httpsPort());
        $this->assertTrue($context->httpsByDefault());

        $context = new UrlGenerationContext('foobar.com', 4000, 8080, false);

        $this->assertSame('foobar.com', $context->host());
        $this->assertSame(8080, $context->httpPort());
        $this->assertSame(4000, $context->httpsPort());
        $this->assertFalse($context->httpsByDefault());
    }

    /**
     * @test
     *
     * @see https://github.com/snicco/snicco/issues/161
     */
    public function test_host_does_not_need_a_dot(): void
    {
        $context = new UrlGenerationContext('nginx');

        $this->assertSame('nginx', $context->host());
        $this->assertSame(80, $context->httpPort());
        $this->assertSame(443, $context->httpsPort());
        $this->assertTrue($context->httpsByDefault());

        $context = new UrlGenerationContext('nginx', 4000, 8080, false);

        $this->assertSame('nginx', $context->host());
        $this->assertSame(8080, $context->httpPort());
        $this->assertSame(4000, $context->httpsPort());
        $this->assertFalse($context->httpsByDefault());
    }

    /**
     * @test
     */
    public function test_exception_for_empty_host(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$host cant be empty');

        new UrlGenerationContext('');
    }

    /**
     * @test
     */
    public function test_exception_if_host_contains_scheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$host must not contain a scheme');

        new UrlGenerationContext('https://foo.com');
    }
}

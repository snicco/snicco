<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use DateInterval;
use Snicco\Component\BetterWPCache\Exception\Psr6InvalidArgumentException;
use Snicco\Component\BetterWPCache\WPCacheItem;

use function time;

/**
 * @internal
 */
final class WPCacheItemTest extends WPTestCase
{
    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_invalid_argument_exception_expires_at(): void
    {
        $item = new WPCacheItem('foo', 'bar', true);

        $this->expectException(Psr6InvalidArgumentException::class);
        $item->expiresAt(10);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_invalid_argument_exception_expires_after(): void
    {
        $item = new WPCacheItem('foo', 'bar', true);

        $this->expectException(Psr6InvalidArgumentException::class);
        $item->expiresAfter('10');
    }

    /**
     * @test
     */
    public function test_expires_after_with_date_interval(): void
    {
        $item = new WPCacheItem('foo', 'bar', true);

        $internal = new DateInterval('PT2S');
        $item->expiresAfter($internal);
        $this->assertEqualsWithDelta(time() + 2, $item->expirationTimestamp(), 0.1);
    }
}

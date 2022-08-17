<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use Snicco\Component\BetterWPDB\KeysetPagination\Query;

/**
 * @internal
 */
final class QueryTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_static_bindings_count_does_not_equal_placeholder_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $cursor = new Query('select * from foo where topic = ?', [
            'id' => 'asc',
        ], 1000);

        unset($cursor);
    }
}

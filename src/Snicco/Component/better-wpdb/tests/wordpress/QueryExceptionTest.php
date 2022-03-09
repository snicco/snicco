<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\Exception\QueryException;

final class QueryExceptionTest extends WPTestCase
{
    /**
     * @test
     */
    public function test_formatting_with_null_column(): void
    {
        $e = new QueryException(
            'error',
            'select * from test where foo = ? and bar = ? and baz = ?',
            [null, 10, 'string']
        );

        $this->assertStringContainsString('error', $e->getMessage());
        $this->assertStringContainsString(
            'Query: [select * from test where foo = ? and bar = ? and baz = ?]',
            $e->getMessage()
        );
        $this->assertStringContainsString("Bindings: [null, 10, 'string']", $e->getMessage());
    }
}

<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\QueryInfo;

use function microtime;

final class QueryInfoTest extends WPTestCase
{

    /**
     * @test
     */
    public function test_time_in_ms(): void
    {
        $start = microtime(true);
        $end = $start + 0.5;

        $query_info = new QueryInfo($start, $end, 'foobar', []);

        $this->assertSame(500.0, $query_info->duration_in_ms);
    }

    /**
     * @test
     */
    public function test_sql_placeholders(): void
    {
        $start = microtime(true);
        $end = $start + 0.5;

        $query_info = new QueryInfo($start, $end, 'select * from test where foo = ? and baz = ?', ['bar', 10.05]);

        $this->assertSame('select * from test where foo = ? and baz = ?', $query_info->sql_with_placeholders);
        $this->assertSame("select * from test where foo = 'bar' and baz = 10.05", $query_info->sql);
    }

    /**
     * @test
     */
    public function test_sql_placeholders_with_null(): void
    {
        $start = microtime(true);
        $end = $start + 0.5;

        $query_info = new QueryInfo($start, $end, 'insert into test (`foo`,`bar`) values (?,?)', ['baz', null]);

        $this->assertSame('insert into test (`foo`,`bar`) values (?,?)', $query_info->sql_with_placeholders);
        $this->assertSame("insert into test (`foo`,`bar`) values ('baz',null)", $query_info->sql);
    }


}
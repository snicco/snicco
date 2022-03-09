<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use mysqli_result;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\QueryException;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;

use const MYSQLI_ASSOC;

final class BetterWPDB_unprepared_Test extends BetterWPDBTestCase
{
    /**
     * @test
     *
     * @psalm-suppress PossiblyNullArrayAccess
     */
    public function test_unprepared_with_read(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
        ]);

        $result = $this->better_wpdb->unprepared("select * from test_table where test_string = 'foo'");
        $this->assertTrue($result instanceof mysqli_result);

        $this->assertSame(1, $result->num_rows);

        $row = $result->fetch_array(MYSQLI_ASSOC);

        $this->assertSame('foo', $row['test_string']);
    }

    /**
     * @test
     */
    public function test_unprepared_with_and_placeholders_throws(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
        ]);

        $this->expectException(QueryException::class);
        $this->better_wpdb->unprepared('select * from test_table where test_string = ?');
    }

    /**
     * @test
     */
    public function test_unprepared_with_writes_returns_bool_true(): void
    {
        $res = $this->better_wpdb->unprepared("insert into test_table (test_string) values ('foo')");
        $this->assertSame(true, $res);
    }

    /**
     * @test
     */
    public function test_unprepared_queries_are_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->unprepared("insert into test_table (test_string, test_float) values ('foo', null)");

        $this->assertTrue(isset($logger->queries[0]));
        $this->assertCount(1, $logger->queries);

        $first = $logger->queries[0];

        $this->assertSame("insert into test_table (test_string, test_float) values ('foo', null)", $first->sql);
        $this->assertSame(
            "insert into test_table (test_string, test_float) values ('foo', null)",
            $first->sql_with_placeholders
        );
        $this->assertSame([], $first->bindings);
        $this->assertTrue($first->end > $first->start);
    }
}

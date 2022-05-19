<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;
use stdClass;

/**
 * @internal
 */
final class BetterWPDB_safeQuery_Test extends BetterWPDBTestCase
{
    /**
     * @test
     */
    public function prepared_queries_can_be_run(): void
    {
        $stmt = $this->better_wpdb->preparedQuery('insert into test_table (test_string) values (?)', ['foo']);
        $this->assertSame(1, $stmt->affected_rows);
    }

    /**
     * @test
     */
    public function prepared_queries_can_be_run_without_placeholders(): void
    {
        $this->better_wpdb->preparedQuery('insert into test_table (test_string) values (?)', ['foo'], false);

        $stmt = $this->better_wpdb->preparedQuery('select count(*) as record_count from test_table');
        $res = $stmt->get_result();
        $row = $res->fetch_array();
        $this->assertTrue(isset($row['record_count']));
        $this->assertSame(1, $row['record_count']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_for_non_scalar_non_null_binding(): void
    {
        $stmt = $this->better_wpdb->preparedQuery(
            'insert into test_table (test_string, test_int) values (?,?)',
            ['foo', null],
            false
        );
        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery('select * from test_table where `test_int` IS NULL');
        $this->assertSame(1, $stmt->get_result()->num_rows);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->preparedQuery(
            'insert into test_table (test_string, test_int) values (?,?)',
            ['foo', new stdClass()]
        );
    }

    /**
     * @test
     */
    public function test_all_queries_are_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->preparedQuery('select * from test_table where test_string = ?', ['foo'], false);

        $this->assertTrue(isset($logger->queries[0]));
        $this->assertCount(1, $logger->queries);

        $this->assertSame('select * from test_table where test_string = ?', $logger->queries[0]->sql_with_placeholders);
        $this->assertSame(['foo'], $logger->queries[0]->bindings);
        $this->assertGreaterThan($logger->queries[0]->start, $logger->queries[0]->end);
    }
}

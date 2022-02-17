<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use stdClass;

final class BetterWPDB_safeQuery_Test extends BetterWPDBTestCase
{

    /**
     * @test
     */
    public function prepared_queries_can_be_run(): void
    {
        $stmt = $this->better_wpdb->safeQuery('insert into test_table (test_string) values (?)', ['foo']);
        $this->assertSame(1, $stmt->affected_rows);
    }

    /**
     * @test
     */
    public function prepared_queries_can_be_run_without_placeholders(): void
    {
        $this->better_wpdb->safeQuery('insert into test_table (test_string) values (?)', ['foo']);

        $stmt = $this->better_wpdb->safeQuery('select count(*) as record_count from test_table');
        $res = $stmt->get_result();
        $row = $res->fetch_array();
        $this->assertTrue(isset($row['record_count']));
        $this->assertSame(1, $row['record_count']);
    }

    /**
     * @test
     */
    public function test_exception_for_non_scalar_non_null_binding(): void
    {
        $stmt = $this->better_wpdb->safeQuery(
            'insert into test_table (test_string, test_int) values (?,?)',
            ['foo', null]
        );
        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->safeQuery('select * from test_table where `test_int` IS NULL');
        $this->assertSame(1, $stmt->get_result()->num_rows);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->safeQuery(
            'insert into test_table (test_string, test_int) values (?,?)',
            ['foo', new stdClass()]
        );
    }

}
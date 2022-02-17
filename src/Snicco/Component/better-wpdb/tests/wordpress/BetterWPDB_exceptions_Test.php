<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Snicco\Component\BetterWPDB\Exception\QueryException;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use wpdb;

use function str_repeat;

final class BetterWPDB_exceptions_Test extends BetterWPDBTestCase
{
    private wpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wpdb = $GLOBALS['wpdb'];
    }

    /**
     * @test
     */
    public function exceptions_are_thrown_for_bad_sql_queries(): void
    {
        // Output buffer because the first wpdb query will print mysql errors to stdout lol.
        ob_start();
        $result = $this->wpdb->query('apparently not a valid SQL statement');
        // bogus query, wpdb no exception, instead output is printed with echo and script resumes.
        $this->assertFalse($result);

        try {
            $this->better_wpdb->safeQuery(
                'apparently not a valid SQL statement',
                ['calvin@web.de', 1]
            );
            $this->fail('No exception thrown for bad query [apparently not a valid SQL statement].');
        } catch (QueryException $e) {
            $this->assertStringContainsString('apparently not a valid SQL statement', $e->getMessage());
            $this->assertStringContainsString('Query: [apparently not a valid SQL statement]', $e->getMessage());
            $this->assertStringContainsString("Bindings: ['calvin@web.de', 1]", $e->getMessage());
        }

        // wpdb is still shitty.
        $result = $this->wpdb->query('apparently not a valid SQL statement');
        $this->assertFalse($result);
        ob_end_clean();
    }

    /**
     * @test
     */
    public function exceptions_related_to_big_column_payload_are_handled(): void
    {
        // test_string is varchar(30)

        $result = $this->wpdb->insert('test_table', [
            'test_string' => str_repeat('X', 30)
        ]);
        $this->assertSame(1, $result);

        $result = $this->wpdb->insert('test_table', [
            'test_string' => str_repeat('X', 31)
        ]);
        // wpdb reports boolean false.
        $this->assertFalse($result);

        try {
            $this->better_wpdb->safeQuery(
                'insert into test_table (test_string) values(?)',
                [str_repeat('X', 31)]
            );
            $this->fail('No exception thrown for bad query.');
        } catch (QueryException $e) {
            $this->assertStringContainsString("Data too long for column 'test_string'", $e->getMessage());
            $this->assertStringContainsString(
                'Query: [insert into test_table (test_string) values(?)]',
                $e->getMessage()
            );
            $this->assertStringContainsString("Bindings: ['XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']", $e->getMessage());
        }

        // wpdb is still not reporting
        $result = $this->wpdb->insert('test_table', [
            'test_string' => str_repeat('X', 31)
        ]);
        $this->assertFalse($result);
    }

    /**
     * @test
     *
     * @psalm-suppress MixedPropertyFetch
     * @psalm-suppress PossiblyInvalidArrayAccess
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     * @psalm-suppress PossiblyNullPropertyFetch
     * @psalm-suppress PossiblyNullArrayAccess
     */
    public function exceptions_related_to_wpdb_loose_mysql_mode_are_thrown(): void
    {
        // test_int is an unsigned integer.

        $result = $this->wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => -10
        ]);
        // invalid data, wpdb no exception, money is clamped to 0.
        // The insert went through with invalid data.
        $this->assertSame(1, $result);
        $stored = $this->wpdb->get_results("select * from test_table where test_string = 'foo'");
        $this->assertSame('0', $stored[0]->test_int);
        $this->assertSame('', $this->wpdb->last_error);

        try {
            $this->better_wpdb->safeQuery(
                'insert into test_table (test_string, test_int) values (?,?)',
                ['bar', -10]
            );
            $this->fail('No exception thrown for bad query.');
        } catch (QueryException $e) {
            $this->assertStringContainsString("Out of range value for column 'test_int'", $e->getMessage());
            $this->assertStringContainsString(
                'Query: [insert into test_table (test_string, test_int) values (?,?)]',
                $e->getMessage()
            );
            $this->assertStringContainsString("Bindings: ['bar', -10]", $e->getMessage());
        }

        // wpdb is still wrong.
        $result = $this->wpdb->insert('test_table', [
            'test_string' => 'baz',
            'test_int' => -10
        ]);
        // invalid data, wpdb no exception, money is clamped to 0.
        // The insert went through with invalid data.
        $this->assertSame(1, $result);
    }

}
<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use EmptyIterator;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use mysqli_stmt;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use stdClass;
use wpdb;

use function ob_end_clean;
use function ob_end_flush;
use function ob_start;
use function str_repeat;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 * @psalm-suppress MixedPropertyFetch
 * @psalm-suppress MixedMethodCall
 * @psalm-suppress PossiblyNullPropertyFetch
 * @psalm-suppress PossiblyUndefinedIntArrayOffset
 * @psalm-suppress PossiblyInvalidArrayAccess
 * @psalm-suppress PossiblyNullArrayAccess
 */
final class BetterWPDBTest extends WPTestCase
{

    private BetterWPDB $better_wpdb;
    private wpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->better_wpdb = BetterWPDB::fromWpdb();
        $this->wpdb = $GLOBALS['wpdb'];
        $this->wpdb->query('COMMIT');
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS emails', []);
        $this->better_wpdb->preparedQuery(
            'CREATE TABLE IF NOT EXISTS `emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(30) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  `money` FLOAT(9,2) UNSIGNED DEFAULT NULL,
  `test_int` INTEGER UNSIGNED DEFAULT NULL,
  `test_bool` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;', []
        );
        $this->better_wpdb->preparedQuery("insert into emails (email) values ('calvin@web.de')", []);
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS emails', []);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function a_prepared_query_can_be_run(): void
    {
        $stmt = $this->better_wpdb->preparedQuery(
            'update emails set email = ? where id = ?',
            ['marlon@web.de', 1]
        );

        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery(
            'update wp_users set user_email = ? where ID = ?',
            ['jon@web.de', 1000]
        );

        $this->assertSame(0, $stmt->affected_rows);
    }

    /**
     * @test
     */
    public function a_prepared_query_works_without_placeholders(): void
    {
        $stmt = $this->better_wpdb->preparedQuery(
            "update emails set email = 'marlon@web.de' where id = 1",
            []
        );

        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery(
            "update emails set email = 'jon@web.de' where ID = 1000",
            []
        );

        $this->assertSame(0, $stmt->affected_rows);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_if_bindings_contains_non_scalar_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');
        $this->better_wpdb->preparedQuery(
            "update emails set email = 'marlon@web.de' where id = 1",
            [new stdClass()]
        );
    }

    /**
     * @test
     * @psalm-suppress MixedMethodCall
     */
    public function exceptions_are_raised_for_bad_sql_queries(): void
    {
        // Output buffer because the first query will print mysql errors.
        ob_start();

        $result = $this->wpdb->query('apparently not a valid SQL statement');
        // bogus query, wpdb no exception, instead output is printed with echo and script resumes.
        $this->assertFalse($result);

        try {
            $this->better_wpdb->preparedQuery(
                'apparently not a valid SQL statement',
                ['calvin@web.de', 1]
            );
            $this->fail('No exception thrown for bad query [apparently not a valid SQL statement].');
        } catch (Exception $e) {
            $this->assertStringContainsString('apparently not a valid SQL statement', $e->getMessage());
        }

        // wpdb is still shitty.
        $result = $this->wpdb->query('apparently not a valid SQL statement');
        $this->assertFalse($result);
        ob_end_clean();
    }

    /**
     * @test
     * @psalm-suppress MixedAssignment
     */
    public function exceptions_related_to_a_loose_mysql_mode_a_raised(): void
    {
        // We made email a varchar(30)

        $result = $this->wpdb->insert('emails', [
            'email' => str_repeat('X', 30)
        ]);
        $this->assertSame(1, $result);

        $result = $this->wpdb->insert('emails', [
            'email' => str_repeat('X', 31)
        ]);
        // wpdb reports boolean false.
        $this->assertFalse($result);

        try {
            $this->better_wpdb->preparedQuery(
                'insert into emails (email) values(?)',
                [str_repeat('X', 31)]
            );
            $this->fail('No exception thrown for bad query.');
        } catch (Exception $e) {
            $this->assertStringContainsString("Data too long for column 'email'", $e->getMessage());
        }

        // wpdb is still shitty
        $result = $this->wpdb->insert('emails', [
            'email' => str_repeat('X', 31)
        ]);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function wrong_column_values_are_handled(): void
    {
        // Output buffer because the first query will print mysql errors.
        ob_start();

        $result = $this->wpdb->insert('emails', [
            'email' => 'marlon@web.de',
            'money' => -10.00
        ]);
        // invalid data, wpdb no exception, money is clamped to 0.
        // The insert went through with invalid data.
        $this->assertSame(1, $result);
        $stored = $this->wpdb->get_results("select * from emails where email = 'marlon@web.de'");
        $this->assertSame('0.00', $stored[0]->money);

        try {
            $this->better_wpdb->preparedQuery(
                'insert into emails (email, money) values (?,?)',
                ['jon@web.de', -10.00]
            );
            $this->fail('No exception thrown for bad query.');
        } catch (Exception $e) {
            $this->assertStringContainsString("Out of range value for column 'money'", $e->getMessage());
        }

        // wpdb is still wrong.
        $result = $this->wpdb->insert('emails', [
            'email' => 'foo@web.de',
            'money' => -10.00
        ]);
        // invalid data, wpdb no exception, money is clamped to 0.
        // The insert went through with invalid data.
        $this->assertSame(1, $result);

        ob_end_flush();
    }

    /**
     * @test
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress PossiblyInvalidArrayAccess
     * @psalm-suppress PossiblyNullArrayAccess
     */
    public function int_and_float_values_are_handled_correctly(): void
    {
        $this->better_wpdb->preparedQuery(
            'insert into emails (email, money, test_int) values (?,?,?)',
            ['m@web.de', 10.05, 5]
        );
        $this->better_wpdb->preparedQuery('COMMIT', []);

        $result = $this->better_wpdb->preparedSelectAll('select * from emails where email = ?', ['m@web.de']);
        // INT returned
        $this->assertSame(5, $result[0]['test_int']);
        // FLOAT returned
        $this->assertSame(10.05, $result[0]['money']);

        $wpdb_result = $this->wpdb->get_results("select * from emails where email = 'm@web.de'", 'ARRAY_A');
        // strings returned
        $this->assertSame('5', $wpdb_result[0]['test_int']);
        $this->assertSame('10.05', $wpdb_result[0]['money']);
    }

    /**
     * @test
     */
    public function test_transactional_can_fail_and_rolls_back(): void
    {
        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db) {
                $res = $db->preparedQuery(
                    'insert into emails (email) values(?)',
                    ['marlon@web.de']
                );
                $this->assertSame(1, $res->affected_rows);

                // duplicate value exception
                $db->preparedQuery(
                    'insert into emails (email) values(?)',
                    ['marlon@web.de']
                );
            });
            $this->fail('No exception thrown for transaction.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("Duplicate entry 'marlon@web.de'", $e->getMessage());
        }

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where email = ?', ['marlon@web.de']);
        $res = $stmt->get_result()->fetch_all();
        $this->assertCount(0, $res);
    }

    /**
     * @test
     */
    public function test_transaction_can_succeed(): void
    {
        $res = $this->better_wpdb->transactional(function (BetterWPDB $db): mysqli_stmt {
            $db->preparedQuery(
                "insert into emails (email) values('marlon@web.de')",
                []
            );
            $stmt = $db->preparedQuery(
                "insert into emails (email) values('jon@web.de')",
                []
            );
            return $stmt;
        });

        $this->assertSame(1, $res->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery('select * from emails', []);
        $res = $stmt->get_result()->fetch_all();
        $this->assertCount(3, $res);
    }

    /**
     * @test
     */
    public function test_nested_transactions_throw_expection(): void
    {
        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db) {
                $db->preparedQuery(
                    "insert into emails (email) values('marlon@web.de')",
                    []
                );
                $db->preparedQuery(
                    "insert into emails (email) values('jon@web.de')",
                    []
                );
                $db->transactional(function () {
                    throw new RuntimeException('should never run.');
                });
            });
            $this->fail('No exception thrown for nested transaction.');
        } catch (LogicException $e) {
            $this->assertSame('Nested transactions are currently not supported.', $e->getMessage());
        }

        $stmt = $this->better_wpdb->preparedQuery('select * from emails', []);
        $res = $stmt->get_result()->fetch_all();
        $this->assertCount(1, $res);
    }

    /**
     * @test
     */
    public function test_preparedSelect(): void
    {
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('jon@web.de')",
            []
        );
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('marlon@web.de')",
            []
        );

        $result = $this->better_wpdb->preparedSelect('select * from emails where id = ?', [1]);
        $this->assertSame(1, $result->num_rows);
        $this->assertSame('calvin@web.de', $result->fetch_object()->email);

        $result = $this->better_wpdb->preparedSelect('select * from emails', []);
        $this->assertSame(3, $result->num_rows);
    }

    /**
     * @test
     */
    public function test_preparedQuery_works_with_boolean_values(): void
    {
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('jon@web.de')",
            []
        );
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('marlon@web.de')",
            []
        );

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where test_bool = ?', [false]);
        $this->assertSame(3, $stmt->get_result()->num_rows);

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where test_bool = ?', [true]);
        $this->assertSame(0, $stmt->get_result()->num_rows);
    }

    /**
     * @test
     */
    public function test_preparedQuery_works_with_null_values(): void
    {
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('marlon@web.de')",
            []
        );

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where money <=> ?', [null]);
        $this->assertSame(2, $stmt->get_result()->num_rows);

        $stmt = $this->better_wpdb->preparedQuery('update emails set money = ? where id = ?', [10.00, 1]);
        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where money <=> ?', [null]);
        $this->assertSame(1, $stmt->get_result()->num_rows);

        $stmt = $this->better_wpdb->preparedQuery('update emails set money = ? where id = ?', [null, 1]);
        $this->assertSame(1, $stmt->affected_rows);

        $stmt = $this->better_wpdb->preparedQuery('select * from emails where money <=> ?', [null]);
        $this->assertSame(2, $stmt->get_result()->num_rows);
    }

    /**
     * @test
     */
    public function test_preparedSelectAll(): void
    {
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('jon@web.de')",
            []
        );
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('marlon@web.de')",
            []
        );

        $result = $this->better_wpdb->preparedSelectAll('select * from emails where ID = ?', [1]);
        $this->assertSame(1, count($result));
        $this->assertContainsOnly('string', array_keys($result[0]));
        $this->assertSame('calvin@web.de', $result[0]['email']);

        $result = $this->better_wpdb->preparedSelectAll('select * from emails', []);
        $this->assertSame(3, count($result));
        $this->assertContainsOnly('array', $result);
        $this->assertSame('calvin@web.de', $result[0]['email']);
        $this->assertSame('jon@web.de', $result[1]['email']);
        $this->assertSame('marlon@web.de', $result[2]['email']);
    }

    /**
     * @test
     */
    public function test_preparedSelectLazy(): void
    {
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('jon@web.de')",
            []
        );
        $this->better_wpdb->preparedQuery(
            "insert into emails (email) values('marlon@web.de')",
            []
        );

        $result = $this->better_wpdb->preparedSelectLazy('select * from emails', []);
        $this->assertInstanceOf(Generator::class, $result);

        $count = 0;

        foreach ($result as $row) {
            $this->assertIsArray($row);
            $this->assertIsInt($row['id']);
            $this->assertIsString($row['email']);
            $count++;
        }

        $this->assertSame(3, $count);
    }

    /**
     * @test
     */
    public function test_updateById_with_array(): void
    {
        $stmt = $this->better_wpdb->updateByPrimaryKey('emails', ['id' => 1], ['email' => 'foo@web.de']);
        $this->assertSame(1, $stmt->affected_rows);
        $this->assertUserEmailIs('foo@web.de', 1);
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateById_with_invalid_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->updateByPrimaryKey('emails', [1], ['email' => 'foo@web.de']);
    }

    /**
     * @test
     */
    public function test_updateByPrimaryKey_without_array_defaults_to_id_col(): void
    {
        $stmt = $this->better_wpdb->updateByPrimaryKey('emails', 1, ['email' => 'foo@web.de', 'money' => 20.20]);
        $this->assertSame(1, $stmt->affected_rows);
        $this->assertUserEmailIs('foo@web.de', 1);
        $this->assertUserMoneyIs(20.20, 1);
    }

    /**
     * @test
     */
    public function test_preparedUpdate(): void
    {
        $stmt = $this->better_wpdb->preparedUpdate(
            'emails',
            ['id' => 1, 'email' => 'calvin@web.de'],
            ['money' => 20.20, 'test_int' => 10]
        );

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertUserEmailIs('calvin@web.de', 1);
        $this->assertUserMoneyIs(20.20, 1);
        $this->assertUserTestIntIs(10, 1);

        $stmt = $this->better_wpdb->preparedUpdate(
            'emails',
            ['id' => 2, 'email' => 'calvin@web.de'],
            ['money' => 20.20]
        );

        $this->assertSame(0, $stmt->affected_rows);
    }

    /**
     * @test
     */
    public function test_preparedUpdate_works_with_null_values_in_conditions_and_changes(): void
    {
        $stmt = $this->better_wpdb->preparedUpdate(
            'emails',
            ['money' => null],
            ['money' => 20.20, 'test_int' => 10]
        );

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertUserEmailIs('calvin@web.de', 1);
        $this->assertUserMoneyIs(20.20, 1);
        $this->assertUserTestIntIs(10, 1);

        $stmt = $this->better_wpdb->preparedUpdate(
            'emails',
            ['money' => null],
            ['money' => 30.20, 'test_int' => 30]
        );
        $this->assertSame(0, $stmt->affected_rows);
    }

    /**
     * @test
     */
    public function test_preparedInsert(): void
    {
        $stmt = $this->better_wpdb->preparedInsert('emails', ['email' => 'foo@web.de', 'money' => 20.20]);
        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(2, $stmt->insert_id);

        $this->assertUserEmailIs('foo@web.de', 2);
        $this->assertUserMoneyIs(20.20, 2);

        $stmt = $this->better_wpdb->preparedInsert('emails', ['email' => 'bar@web.de', 'money' => 10.20]);
        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(3, $stmt->insert_id);

        $this->assertUserEmailIs('bar@web.de', 3);
        $this->assertUserMoneyIs(10.20, 3);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_preparedInsert_throws_exception_for_empty_record(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty array');
        $this->better_wpdb->preparedInsert('emails', []);
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_preparedInsert_throws_exception_if_not_all_array_keys_are_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All column names must be a non-empty-strings.');
        $this->better_wpdb->preparedInsert('emails', ['calvin@web.de']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_preparedInsert_throws_exception_for_empty_string_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All column names must be a non-empty-strings.');
        $this->better_wpdb->preparedInsert('emails', ['' => 'calvin@web.de']);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_preparedInsert_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A table name must be a non-empty-string.');
        $this->better_wpdb->preparedInsert('', ['email' => 'calvin@web.de']);
    }

    /**
     * @test
     */
    public function test_preparedBulkInsert(): void
    {
        $inserted = $this->better_wpdb->preparedBulkInsert('emails', [
                ['email' => 'foo@web.de', 'money' => 20.20],
                ['email' => 'bar@web.de', 'money' => 10.20],
            ]
        );

        $this->assertSame(2, $inserted);

        $this->assertUserEmailIs('foo@web.de', 2);
        $this->assertUserMoneyIs(20.20, 2);

        $this->assertUserEmailIs('bar@web.de', 3);
        $this->assertUserMoneyIs(10.20, 3);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_preparedBulkInsert_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A table name must be a non-empty-string.');
        $this->better_wpdb->preparedBulkInsert('', [['email' => 'calvin@web.de']]);
    }

    /**
     * @test
     */
    public function test_preparedBulkInsert_with_iterator(): void
    {
        $generator = function (): Generator {
            yield ['email' => 'foo@web.de', 'money' => 20.20];
            yield ['email' => 'bar@web.de', 'money' => 10.20];
        };

        $inserted = $this->better_wpdb->preparedBulkInsert('emails', $generator());

        $this->assertSame(2, $inserted);

        $this->assertUserEmailIs('foo@web.de', 2);
        $this->assertUserMoneyIs(20.20, 2);

        $this->assertUserEmailIs('bar@web.de', 3);
        $this->assertUserMoneyIs(10.20, 3);
    }

    /**
     * @test
     */
    public function test_prepared_bulk_insert_fails_all_if_one_insert_does_not_work(): void
    {
        try {
            $this->better_wpdb->preparedBulkInsert('emails', [
                    ['email' => 'foo@web.de', 'money' => 20.20],
                    ['email' => 'bar@web.de', 'money' => 10.20],
                    ['email' => 'baz@web.de', 'money' => 30.20],
                    // duplicate email
                    ['email' => 'foo@web.de', 'money' => 40.20],
                ]
            );
            $this->fail('Bulk insert should have been rolled back');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Duplicate entry', $e->getMessage());
        }

        $this->assertRecordCount(1);
    }

    /**
     * @test
     */
    public function test_bulk_insert_returns_null_for_empty_iterator(): void
    {
        $this->assertSame(0, $this->better_wpdb->preparedBulkInsert('emails', []));

        $this->assertSame(0, $this->better_wpdb->preparedBulkInsert('emails', new EmptyIterator()));
    }

    /**
     * @test
     */
    public function test_exception_if_first_record_is_empty_for_bulk_insert(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty');

        $this->better_wpdb->preparedBulkInsert('emails', [
            [

            ]
        ]);
    }

    /**
     * @test
     */
    public function test_exception_if_records_are_not_consistent_types(): void
    {
        try {
            $this->better_wpdb->preparedBulkInsert('emails', [
                    ['email' => 'foo@web.de', 'money' => 20.20],
                    ['email' => 'bar@web.de', 'money' => 10.20],
                    ['email' => 'baz@web.de', 'money' => 30.20],
                    ['email' => 'boo@web.de', 'money' => 40],
                ]
            );
            $this->fail('Bulk insert should have been rolled back');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                "Records are not of consistent type.\nExpected: [string,double] and got [string,integer]",
                $e->getMessage()
            );
        }

        $this->assertRecordCount(1);
    }

    private function assertUserEmailIs(string $expected, int $id): void
    {
        $row = $this->better_wpdb->preparedSelect('select email from emails where id = ?', [$id]);
        $std = $row->fetch_object();
        $this->assertSame($expected, $std->email);
    }

    private function assertRecordCount(int $expected): void
    {
        $count = $this->better_wpdb->preparedQuery('select count(*) as count from emails', []);
        $count = $count->get_result()->fetch_object();
        $this->assertSame($expected, $count->count);
    }

    private function assertUserMoneyIs(float $expected, int $id): void
    {
        $row = $this->better_wpdb->preparedSelect('select money from emails where id = ?', [$id]);
        $std = $row->fetch_object();
        $this->assertSame($expected, $std->money);
    }

    private function assertUserTestIntIs(int $expected, int $id): void
    {
        $row = $this->better_wpdb->preparedSelect('select test_int from emails where id = ?', [$id]);
        $std = $row->fetch_object();
        $this->assertSame($expected, $std->test_int);
    }


}
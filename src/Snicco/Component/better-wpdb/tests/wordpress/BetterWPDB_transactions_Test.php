<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use LogicException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\QueryException;
use Snicco\Component\BetterWPDB\QueryInfo;
use Snicco\Component\BetterWPDB\QueryLogger;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;

/**
 * @internal
 */
final class BetterWPDB_transactions_Test extends BetterWPDBTestCase
{
    /**
     * @test
     */
    public function test_transactional_can_fail_and_roll_back(): void
    {
        $this->assertRecordCount(0);

        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db): void {
                $res = $db->insert('test_table', [
                    'test_string' => 'foo',
                ]);
                $this->assertSame(1, $res->affected_rows);

                $db->insert('test_table', [
                    'test_string' => 'foo',
                ]);
            });
            $this->fail('No exception thrown for transaction.');
        } catch (QueryException $e) {
            $this->assertStringContainsString("Duplicate entry 'foo'", $e->getMessage());
        }

        $this->assertRecordCount(0);
    }

    /**
     * @test
     */
    public function test_transactional_can_succeed(): void
    {
        $this->assertRecordCount(0);

        $return = $this->better_wpdb->transactional(function (BetterWPDB $db): string {
            $db->insert('test_table', [
                'test_string' => 'foo',
            ]);
            $db->insert('test_table', [
                'test_string' => 'bar',
            ]);

            return 'foobar';
        });

        $this->assertRecordCount(2);
        $this->assertSame('foobar', $return);
    }

    /**
     * @test
     */
    public function test_nested_transactions_throw_exception(): void
    {
        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db): void {
                $db->transactional(function (): void {
                    throw new RuntimeException('should never run.');
                });
            });
            // @noRector
            $this->fail('No exception thrown for nested transaction.');
        } catch (LogicException $e) {
            $this->assertSame('Nested transactions are currently not supported.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_transactions_are_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->transactional(function (BetterWPDB $db): void {
            $db->insert('test_table', [
                'test_string' => 'foo',
            ]);
            $db->insert('test_table', [
                'test_string' => 'bar',
            ]);
        });

        $this->assertTrue(isset($logger->queries[0]));
        $this->assertTrue(isset($logger->queries[1]));
        $this->assertTrue(isset($logger->queries[2]));
        $this->assertTrue(isset($logger->queries[3]));

        $being_trans = $logger->queries[0];
        $this->assertSame('START TRANSACTION', $being_trans->sql_with_placeholders);
        $this->assertSame([], $being_trans->bindings);
        $this->assertGreaterThan($being_trans->start, $being_trans->end);

        $first_insert = $logger->queries[1];
        $this->assertSame('insert into `test_table` (`test_string`) values (?)', $first_insert->sql_with_placeholders);
        $this->assertSame(['foo'], $first_insert->bindings);
        $this->assertGreaterThan($first_insert->start, $first_insert->end);

        $second_insert = $logger->queries[2];
        $this->assertSame('insert into `test_table` (`test_string`) values (?)', $second_insert->sql_with_placeholders);
        $this->assertSame(['bar'], $second_insert->bindings);
        $this->assertGreaterThan($second_insert->start, $second_insert->end);

        $commit = $logger->queries[3];
        $this->assertSame('COMMIT', $commit->sql_with_placeholders);
        $this->assertSame([], $commit->bindings);
        $this->assertGreaterThan($commit->start, $commit->end);
    }

    /**
     * @test
     */
    public function an_error_during_logging_the_commit_query_doesnt_rollback_the_already_commit_transaction(): void
    {
        $logger = new class() implements QueryLogger {
            /**
             * @var QueryInfo[]
             */
            public array $queries = [];

            public function log(QueryInfo $info): void
            {
                if ('COMMIT' === $info->sql_with_placeholders) {
                    throw new RuntimeException('cant log.');
                }

                $this->queries[] = $info;
            }
        };
        $db = BetterWPDB::fromWpdb($logger);

        try {
            $db->transactional(function (BetterWPDB $db): void {
                $db->insert('test_table', [
                    'test_string' => 'foo',
                ]);
            });
            $this->fail('no exception thrown');
        } catch (RuntimeException $e) {
            $this->assertSame('cant log.', $e->getMessage());
        }

        $this->assertRecordCount(1);
    }
}

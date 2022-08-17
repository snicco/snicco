<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use LogicException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\KeysetPagination\Lock;
use Snicco\Component\BetterWPDB\KeysetPagination\Query;

use function array_fill;
use function array_merge;
use function array_pop;
use function range;

/**
 * @internal
 */
final class BetterWPDB_keyset_pagination_Test extends WPTestCase
{
    private BetterWPDB $better_wpdb;

    protected function setUp(): void
    {
        $this->better_wpdb = BetterWPDB::fromWpdb();
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS test_table', []);
        $this->better_wpdb->preparedQuery(
            'CREATE TABLE IF NOT EXISTS `test_table` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `test_string` varchar(30) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  `test_float` FLOAT(9,2) UNSIGNED DEFAULT NULL,
  `test_int` INTEGER UNSIGNED DEFAULT NULL,
  `test_bool` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;',
            []
        );

        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS keyword_tracker', []);
        $this->better_wpdb->preparedQuery(
            'CREATE TABLE IF NOT EXISTS `keyword_tracker` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `page_id` integer not null,
  `keyword` varchar(20),
  `impressions` integer unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY(`page_id`, `keyword`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;',
            []
        );
        $this->better_wpdb->bulkInsert('keyword_tracker', [
            [
                'page_id' => 1,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 2,
                'keyword' => '15',
                'impressions' => 15,
            ],
            [
                'page_id' => 1,
                'keyword' => '10',
                'impressions' => 10,
            ],
            [
                'page_id' => 2,
                'keyword' => '20',
                'impressions' => 20,
            ],
            [
                'page_id' => 2,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 2,
                'keyword' => '6',
                'impressions' => 6,
            ],
        ]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS keyword_tracker');
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS test_table');
    }

    /**
     * @test
     */
    public function batch_process_with_one_sorting_column_ascending(): void
    {
        $this->generateRecords(50);
        $batch_size = 10;

        $cursor = new Query(
            'select `id`, `test_int` from test_table',
            [
                'id' => 'asc',
            ],
            $batch_size
        );

        $batch_count = 0;
        $ids = [];

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$batch_count, &$ids, &$batch_size) {
            $batch_size = (int) $batch_size;
            $batch_count = (int) $batch_count;
            $ids = (array) $ids;

            /**
             * @var int   $index
             * @var array $record
             */
            foreach ($records as $index => $record) {
                $expected_int = (int) ($index + 1 + ($batch_size * $batch_count));

                $this->assertSame([
                    'id' => $expected_int,
                    'test_int' => $expected_int,
                ], $record);

                $ids[] = $record['id'];
            }

            ++$batch_count;
        });

        $this->assertSame(5, $batch_count);
        $this->assertSame(range(1, 50), $ids);
    }

    /**
     * @test
     */
    public function batch_process_with_one_sorting_column_descending(): void
    {
        $this->generateRecords(50);
        $batch_size = 10;

        $cursor = new Query(
            'select `id` from test_table',
            [
                'id' => 'desc',
            ],
            $batch_size
        );

        $batch_count = 0;
        $ids = [];
        $expected_id = 50;

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (
            &$batch_count,
            &$ids,
            &$expected_id
        ) {
            // psalm
            $expected_id = (int) $expected_id;
            $batch_count = (int) $batch_count;
            $ids = (array) $ids;

            foreach ($records as $record) {
                $this->assertSame([
                    'id' => $expected_id,
                ], $record);

                --$expected_id;
                $ids[] = $record['id'];
            }
            ++$batch_count;
        });

        $this->assertSame(5, $batch_count);
        $this->assertSame(range(50, 1), $ids);
    }

    /**
     * @test
     */
    public function batch_process_with_multiple_sorting_column_ascending(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword`, `impressions` from keyword_tracker',
            [
                'page_id' => 'asc',
                'impressions' => 'asc',
            ],
            2
        );

        $all = [];
        $batches_count = 0;

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$all, &$batches_count): void {
            $batches_count = (int) $batches_count;
            $all = array_merge((array) $all, $records);
            ++$batches_count;
        });

        $this->assertSame(3, $batches_count);

        $this->assertSame([
            [
                'page_id' => 1,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 1,
                'keyword' => '10',
                'impressions' => 10,
            ],
            // page 2
            [
                'page_id' => 2,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 2,
                'keyword' => '6',
                'impressions' => 6,
            ],
            [
                'page_id' => 2,
                'keyword' => '15',
                'impressions' => 15,
            ],
            [
                'page_id' => 2,
                'keyword' => '20',
                'impressions' => 20,
            ],
        ], $all);
    }

    /**
     * @test
     */
    public function batch_process_with_multiple_sorting_column_descending(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword`, `impressions` from keyword_tracker',
            [
                'page_id' => 'desc',
                'impressions' => 'desc',
            ],
            2
        );

        $all = [];

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$all): void {
            $all = array_merge((array) $all, $records);
        });

        $this->assertSame([
            // page 2
            [
                'page_id' => 2,
                'keyword' => '20',
                'impressions' => 20,
            ],
            [
                'page_id' => 2,
                'keyword' => '15',
                'impressions' => 15,
            ],
            [
                'page_id' => 2,
                'keyword' => '6',
                'impressions' => 6,
            ],
            [
                'page_id' => 2,
                'keyword' => '5',
                'impressions' => 5,
            ],

            // page 1
            [
                'page_id' => 1,
                'keyword' => '10',
                'impressions' => 10,
            ],
            [
                'page_id' => 1,
                'keyword' => '5',
                'impressions' => 5,
            ],
        ], $all);
    }

    /**
     * @test
     */
    public function batch_process_works_with_ascending_and_descending_columns_mixed(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword`, `impressions` from keyword_tracker',
            [
                'page_id' => 'desc',
                'impressions' => 'asc',
            ],
            2
        );

        $all = [];

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$all): void {
            $all = array_merge((array) $all, $records);
        });

        $this->assertSame([
            // page 2
            [
                'page_id' => 2,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 2,
                'keyword' => '6',
                'impressions' => 6,
            ],
            [
                'page_id' => 2,
                'keyword' => '15',
                'impressions' => 15,
            ],
            [
                'page_id' => 2,
                'keyword' => '20',
                'impressions' => 20,
            ],

            // page 1
            [
                'page_id' => 1,
                'keyword' => '5',
                'impressions' => 5,
            ],
            [
                'page_id' => 1,
                'keyword' => '10',
                'impressions' => 10,
            ],
        ], $all);
    }

    /**
     * @test
     */
    public function batch_process_works_with_existing_conditions(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword` from keyword_tracker where page_id = ?',
            [
                'page_id' => 'desc',
            ],
            2,
            [1]
        );

        $all = [];

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$all): void {
            $all = array_merge((array) $all, $records);
        });

        $this->assertSame([
            // page 1
            [
                'page_id' => 1,
                'keyword' => '10',
            ],
            [
                'page_id' => 1,
                'keyword' => '5',
            ],
        ], $all);
    }

    /**
     * @test
     */
    public function batch_process_works_with_empty_records_on_existing_conditions(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword` from keyword_tracker where keyword = ?',
            [
                'page_id' => 'desc',
            ],
            2,
            [20]
        );

        $all = [];

        $this->better_wpdb->batchProcess($cursor, function (array $records) use (&$all): void {
            $all = array_merge((array) $all, $records);
        });

        $this->assertSame([
            [
                'page_id' => 2,
                'keyword' => '20',
            ],
        ], $all);
    }

    /**
     * @test
     */
    public function batch_process_can_wrap_each_batch_in_transactions(): void
    {
        $cursor = new Query(
            'select `page_id`, `keyword` from keyword_tracker',
            [
                'page_id' => 'desc',
            ],
            3,
        );

        try {
            $this->better_wpdb->batchProcess($cursor, function (array $records): void {
                unset($records);

                /**
                 * @var int
                 */
                static $count = 0;

                if ($count > 0) {
                    $this->better_wpdb->insert('keyword_tracker', [
                        'page_id' => 1,
                        'keyword' => 'failed',
                        'impressions' => '10',
                    ]);

                    throw new RuntimeException('This query did not work');
                }
                $this->better_wpdb->insert('keyword_tracker', [
                    'page_id' => 1,
                    'keyword' => 'new',
                    'impressions' => '10',
                ]);
                ++$count;
            }, Lock::forRead());

            $this->fail('Should have thrown exception');
        } catch (RuntimeException $e) {
            $this->assertSame('This query did not work', $e->getMessage());
        }

        $this->assertFalse(
            $this->better_wpdb->exists('keyword_tracker', [
                'page_id' => 1,
                'keyword' => 'failed',
            ])
        );

        $this->assertTrue(
            $this->better_wpdb->exists('keyword_tracker', [
                'page_id' => 1,
                'keyword' => 'new',
            ])
        );
    }

    /**
     * @test
     */
    public function that_results_can_be_returned_from_a_batch_process(): void
    {
        $this->generateRecords(50);
        $batch_size = 10;

        $cursor = new Query(
            'select `id`, `test_int` from test_table',
            [
                'id' => 'asc',
            ],
            $batch_size
        );

        $result = $this->better_wpdb->batchProcess($cursor, function () {
            return 'foo';
        });

        $this->assertSame(array_fill(0, 5, 'foo'), $result);

        $result = $this->better_wpdb->batchProcess($cursor, function () {
            return ['foo'];
        });
        $this->assertSame(array_fill(0, 5, ['foo']), $result);

        // With transactions.
        $result = $this->better_wpdb->batchProcess($cursor, function () {
            return 'foo';
        }, Lock::forReadWrite());

        $this->assertSame(array_fill(0, 5, 'foo'), $result);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_not_all_sorting_columns_are_in_the_result_set(): void
    {
        $cursor = new Query(
            'select `page_id` from keyword_tracker',
            [
                'page_id' => 'desc',
                'impressions' => 'desc',
            ],
            2,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('impressions');

        $this->better_wpdb->batchProcess($cursor, function (): void {
            // No-op
        });
    }

    /**
     * @test
     *
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function that_keyset_pagination_works(): void
    {
        $this->generateRecords(55);

        $cursor = new Query(
            'select `id` from test_table',
            [
                'id' => Query::SORT_ASC,
            ],
            20
        );

        // Without a previous result
        $result = $this->better_wpdb->keysetPaginate($cursor);

        $this->assertCount(20, $result);
        $this->assertSame(1, $result->records[0]['id']);
        $last = array_pop($result->records);
        $this->assertSame([
            'id' => 20,
        ], $last);
        $this->assertFalse($result->is_last);

        // With a previous result
        $result = $this->better_wpdb->keysetPaginate(
            $cursor,
            $result->left_off
        );

        $this->assertCount(20, $result);
        $this->assertSame(21, $result->records[0]['id']);
        $last = array_pop($result->records);
        $this->assertSame([
            'id' => 40,
        ], $last);
        $this->assertFalse($result->is_last);

        // With a previous result that has no further results
        $result = $this->better_wpdb->keysetPaginate(
            $cursor,
            $result->left_off
        );

        $this->assertCount(15, $result);
        $this->assertSame(41, $result->records[0]['id']);
        $last = array_pop($result->records);
        $this->assertSame([
            'id' => 55,
        ], $last);
        $this->assertTrue($result->is_last);
    }

    /**
     * @test
     */
    public function test_keyset_pagination_with_multiple_sorting_columns(): void
    {
        $cursor = new Query(
            'select `page_id`, `impressions` from keyword_tracker',
            [
                'page_id' => 'desc',
                'impressions' => 'asc',
            ],
            3
        );

        // First query without previous results.
        $result = $this->better_wpdb->keysetPaginate($cursor);

        $this->assertCount(3, $result);
        $this->assertSame([
            [
                'page_id' => 2,
                'impressions' => 5,
            ],
            [
                'page_id' => 2,
                'impressions' => 6,
            ],
            [
                'page_id' => 2,
                'impressions' => 15,
            ],
        ], $result->records);

        // Query with previous result.
        $result = $this->better_wpdb->keysetPaginate($cursor, $result->left_off);
        $this->assertCount(3, $result);
        $this->assertSame([
            [
                'page_id' => 2,
                'impressions' => 20,
            ],
            [
                'page_id' => 1,
                'impressions' => 5,
            ],
            [
                'page_id' => 1,
                'impressions' => 10,
            ],
        ], $result->records);

        // With exhausted results
        $result = $this->better_wpdb->keysetPaginate($cursor, $result->left_off);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function test_keyset_pagination_works_with_conditions(): void
    {
        $cursor = new Query(
            'select `page_id`, `impressions` from keyword_tracker where `keyword` = ?',
            [
                'page_id' => 'desc',
                'impressions' => 'asc',
            ],
            10,
            [20]
        );

        // First query without previous results.
        $result = $this->better_wpdb->keysetPaginate($cursor);

        $this->assertCount(1, $result);
        $this->assertSame([
            [
                'page_id' => 2,
                'impressions' => 20,
            ],
        ], $result->records);

        // Query with previous result.
        $result = $this->better_wpdb->keysetPaginate($cursor, $result->left_off);
        $this->assertCount(0, $result);
    }

    private function generateRecords(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $this->better_wpdb->preparedQuery(
                "insert into test_table (test_string, test_int, test_float, test_bool) values('foo_{$i}', {$i}, 10.00 * {$i}, true )",
            );
        }
    }
}

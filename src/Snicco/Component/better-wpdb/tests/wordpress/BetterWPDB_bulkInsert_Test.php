<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use EmptyIterator;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;
use stdClass;

/**
 * @internal
 */
final class BetterWPDB_bulkInsert_Test extends BetterWPDBTestCase
{
    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_bulk_insert_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->bulkInsert('', [
            [
                'test_string' => 'foo',
            ],
        ]);
    }

    /**
     * @test
     */
    public function test_bulk_insert_throws_exception_for_empty_record(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-array');

        $this->better_wpdb->bulkInsert('test_table', [[]]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_bulk_insert_throws_exception_for_non_string_record_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->delete('test_table', [['foo']]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_bulk_insert_throws_exception_for_empty_string_record_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->delete('test_table', [
            [
                '' => 'foo',
            ],
        ]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_bulk_insert_throws_exception_non_scalar_record_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');
        $this->better_wpdb->bulkInsert('test_table', [
            [
                'test_string' => new stdClass(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function test_bulk_insert_throws_exception_for_inconsistent_record_types(): void
    {
        try {
            $this->better_wpdb->bulkInsert(
                'test_table',
                [
                    [
                        'test_string' => 'foo',
                        'test_float' => 10.00,
                        'test_int' => 1,
                    ],
                    [
                        'test_string' => 'bar',
                        'test_int' => 2,
                        'test_float' => 20.00,
                    ],
                ]
            );
            $this->fail('Bulk insert should have been rolled back');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                "Records are not of consistent type.\nExpected: [string,double,integer] and got [string,integer,double] for record 2.",
                $e->getMessage()
            );
        }

        $this->assertRecordCount(0);
    }

    /**
     * @test
     */
    public function test_bulk_delete_returns_zero_for_empty_record_iterator(): void
    {
        $res = $this->better_wpdb->bulkInsert('test_table', []);
        $this->assertSame(0, $res);

        $res = $this->better_wpdb->bulkInsert('test_table', new EmptyIterator());
        $this->assertSame(0, $res);
    }

    /**
     * @test
     */
    public function test_bulk_insert_with_array(): void
    {
        $this->assertRecordCount(0);

        $res = $this->better_wpdb->bulkInsert(
            'test_table',
            [
                [
                    'test_string' => 'foo',
                    'test_float' => 10.00,
                    'test_int' => 1,
                ],
                [
                    'test_string' => 'bar',
                    'test_float' => 20.00,
                    'test_int' => 2,
                ],
            ]
        );

        $this->assertSame(2, $res);
        $this->assertRecordCount(2);

        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_float' => 10.00,
            'test_int' => 1,
        ]);

        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_float' => 20.00,
            'test_int' => 2,
        ]);
    }

    /**
     * @test
     */
    public function test_bulk_insert_with_iterator(): void
    {
        $this->assertRecordCount(0);

        $generator = function (): Generator {
            yield [
                'test_string' => 'foo',
                'test_float' => 10.00,
                'test_int' => 1,
            ];
            yield [
                'test_string' => 'bar',
                'test_float' => 20.00,
                'test_int' => 2,
            ];
        };

        $res = $this->better_wpdb->bulkInsert('test_table', $generator());
        $this->assertSame(2, $res);

        $this->assertRecordCount(2);

        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_float' => 10.00,
            'test_int' => 1,
        ]);

        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_float' => 20.00,
            'test_int' => 2,
        ]);
    }

    /**
     * @test
     */
    public function test_bulk_insert_rolls_back_everything_if_not_all_records_can_be_inserted(): void
    {
        $this->assertRecordCount(0);

        try {
            $this->better_wpdb->bulkInsert(
                'test_table',
                [
                    [
                        'test_string' => 'foo',
                        'test_float' => 10.00,
                        'test_int' => 1,
                    ],
                    [
                        'test_string' => 'bar',
                        'test_float' => 20.00,
                        'test_int' => 2,
                    ],
                    // duplicate string.
                    [
                        'test_string' => 'bar',
                        'test_float' => 30.00,
                        'test_int' => 4,
                    ],
                ]
            );
            $this->fail('Bulk insert should not have succeeded.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Duplicate entry', $e->getMessage());
        }

        $this->assertRecordCount(0);
    }

    /**
     * @test
     */
    public function test_bulk_insert_is_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->bulkInsert(
            'test_table',
            [
                [
                    'test_string' => 'foo',
                    'test_float' => 10.00,
                    'test_int' => 1,
                ],
                [
                    'test_string' => 'bar',
                    'test_float' => 20.00,
                    'test_int' => 2,
                ],
            ]
        );

        $this->assertCount(4, $logger->queries);
        $this->assertTrue(isset($logger->queries[0]));
        $this->assertTrue(isset($logger->queries[1]));
        $this->assertTrue(isset($logger->queries[2]));
        $this->assertTrue(isset($logger->queries[3]));

        $this->assertSame(
            'insert into `test_table` (`test_string`,`test_float`,`test_int`) values (?,?,?)',
            $logger->queries[1]->sql_with_placeholders
        );
        $this->assertSame(['foo', 10.00, 1], $logger->queries[1]->bindings);
        $this->assertTrue($logger->queries[1]->end > $logger->queries[1]->start);

        $this->assertSame(
            'insert into `test_table` (`test_string`,`test_float`,`test_int`) values (?,?,?)',
            $logger->queries[2]->sql_with_placeholders
        );
        $this->assertSame(['bar', 20.00, 2], $logger->queries[2]->bindings);
        $this->assertTrue($logger->queries[2]->end > $logger->queries[2]->start);
    }
}

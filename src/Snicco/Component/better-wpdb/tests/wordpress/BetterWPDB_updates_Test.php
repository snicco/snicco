<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;
use stdClass;

final class BetterWPDB_updates_Test extends BetterWPDBTestCase
{

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_with_empty_table_name_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->updateByPrimary('', 1, ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_empty_string_primary_key_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->updateByPrimary('test_table', '', ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_for_non_string_array_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->updateByPrimary('test_table', ['id'], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_for_empty_string_array_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->updateByPrimary('test_table', ['' => 'id'], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_non_string_key_change(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->updateByPrimary('test_table', 1, ['foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_for_empty_string_key_change(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->updateByPrimary('test_table', 1, ['' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_for_non_scalar_changes_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->updateByPrimary('test_table', 1, ['test_string' => new stdClass()]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_updateByPrimary_throws_exception_for_empty_changes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('empty array');

        $this->better_wpdb->updateByPrimary('test_table', 1, []);
    }

    /**
     * @test
     */
    public function test_updateByPrimary_with_scalar_primary_key(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10
        ]);

        $res = $this->better_wpdb->updateByPrimary('test_table', 2, [
            'test_string' => 'bar'
        ]);
        $this->assertSame(0, $res);

        $res = $this->better_wpdb->updateByPrimary('test_table', 1, [
            'test_string' => 'bar',
            'test_int' => 20,
        ]);
        $this->assertSame(1, $res);

        $this->assertRecord(1, [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => null,
            'test_bool' => 0
        ]);
    }

    /**
     * @test
     */
    public function test_updateByPrimary_with_array_primary_key(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10
        ]);

        $res = $this->better_wpdb->updateByPrimary('test_table', ['id' => 2], [
            'test_string' => 'bar'
        ]);
        $this->assertSame(0, $res);

        $res = $this->better_wpdb->updateByPrimary('test_table', ['id' => 1], [
            'test_string' => 'bar',
            'test_int' => 20,
        ]);
        $this->assertSame(1, $res);

        $this->assertRecord(1, [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => null,
            'test_bool' => 0
        ]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_with_empty_table_name_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->update('', ['test_string' => 'foo'], ['test_string' => 'bar']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_empty_string_condition_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->update('test_table', ['' => 'foo'], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_empty_string_in_changes_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->update('test_table', ['test_string' => 'foo'], ['' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_non_string_key_in_conditions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->update('test_table', ['foo'], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_non_string_key_in_changes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty-string');

        $this->better_wpdb->update('test_table', ['test_string' => 'foo'], ['foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_non_scalar_in_conditions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->update('test_table', ['test_string' => new stdClass()], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_non_scalar_in_changes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->update('test_table', ['test_string' => 'foo'], ['test_string' => new stdClass()]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_empty_conditions(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('empty array');

        $this->better_wpdb->update('test_table', [], ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_update_throws_exception_with_empty_changes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('empty array');

        $this->better_wpdb->update('test_table', ['test_string' => 'foo'], []);
    }

    /**
     * @test
     */
    public function test_update_works(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10
        ]);
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'bar',
            'test_int' => 10
        ]);

        $res = $this->better_wpdb->update('test_table',
            ['test_int' => 20],
            ['test_bool' => true]
        );
        $this->assertSame(0, $res);

        $res = $this->better_wpdb->update('test_table',
            ['test_int' => 10],
            ['test_bool' => true]
        );
        $this->assertSame(2, $res);

        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_int' => 10,
            'test_float' => null,
            'test_bool' => 1
        ]);
        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_int' => 10,
            'test_float' => null,
            'test_bool' => 1
        ]);

        $res = $this->better_wpdb->update('test_table',
            ['test_float' => null],
            ['test_float' => 20.00]
        );
        $this->assertSame(2, $res);

        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_int' => 10,
            'test_float' => 20.00,
            'test_bool' => 1
        ]);
        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_int' => 10,
            'test_float' => 20.00,
            'test_bool' => 1
        ]);

        $res = $this->better_wpdb->update('test_table',
            ['test_float' => null],
            ['test_float' => 20.00]
        );
        $this->assertSame(0, $res);
    }

    /**
     * @test
     */
    public function test_update_queries_are_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $this->assertCount(0, $logger->queries);

        $db->update('test_table', ['test_string' => 'foo'], ['test_string' => 'bar']);

        $this->assertTrue(isset($logger->queries[0]));

        $this->assertSame(
            'update `test_table` set `test_string` = ? where `test_string` = ?',
            $logger->queries[0]->sql
        );
        $this->assertSame(['bar', 'foo'], $logger->queries[0]->bindings);
        $this->assertTrue($logger->queries[0]->end > $logger->queries[0]->start);
    }

    /**
     * @test
     */
    public function test_updateByPrimary_is_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $this->assertCount(0, $logger->queries);

        $db->updateByPrimary('test_table', 1, ['test_string' => 'bar']);

        $this->assertTrue(isset($logger->queries[0]));

        $this->assertSame(
            'update `test_table` set `test_string` = ? where `id` = ?',
            $logger->queries[0]->sql
        );
        $this->assertSame(['bar', 1], $logger->queries[0]->bindings);
        $this->assertTrue($logger->queries[0]->end > $logger->queries[0]->start);
    }

}
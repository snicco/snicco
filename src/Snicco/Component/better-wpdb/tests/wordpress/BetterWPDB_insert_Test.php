<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use stdClass;

final class BetterWPDB_insert_Test extends BetterWPDBTestCase
{

    /**
     * @test
     */
    public function test_insert(): void
    {
        $this->assertRecordCount(0);

        $stmt = $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10
        ]);

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(1, $stmt->insert_id);
        $this->assertRecordCount(1);
        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_int' => 10,
            'test_float' => null,
            'test_bool' => 0
        ]);

        $stmt = $this->better_wpdb->insert('test_table', [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => 10.00,
            'test_bool' => true
        ]);

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(2, $stmt->insert_id);
        $this->assertRecordCount(2);
        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => 10.00,
            'test_bool' => 1
        ]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('', ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty array');

        $this->better_wpdb->insert('test_table', []);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_non_string_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', ['foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_string_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', ['' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_non_scalar_data_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->insert('test_table', ['test_string' => new stdClass()]);
    }

    /**
     * @test
     */
    public function test_insert_throws_exception_for_multi_dimensional_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', [['test_string' => 'foo']]);
    }


}
<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;
use stdClass;

final class BetterWPDB_delete_Test extends BetterWPDBTestCase
{
    /**
     * @test
     */
    public function test_delete(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo'
        ]);

        $this->better_wpdb->insert('test_table', [
            'test_string' => 'bar',
            'test_int' => 1,
            'test_bool' => true,
        ]);
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'baz',
            'test_int' => 1,
            'test_float' => 10.00,
            'test_bool' => false,
        ]);

        $this->assertRecordCount(3);

        $res = $this->better_wpdb->delete('test_table', ['test_string' => 'foo']);
        $this->assertSame(1, $res);

        $this->assertRecordCount(2);

        $res = $this->better_wpdb->delete('test_table', ['test_string' => 'bogus']);
        $this->assertSame(0, $res);

        $this->assertRecordCount(2);

        $res = $this->better_wpdb->delete('test_table', ['test_string' => 'baz', 'test_float' => null]);
        $this->assertSame(0, $res);

        $this->assertRecordCount(2);

        $res = $this->better_wpdb->delete('test_table', ['test_string' => 'baz', 'test_float' => 10.00]);
        $this->assertSame(1, $res);

        $this->assertRecordCount(1);

        $res = $this->better_wpdb->delete('test_table', ['test_float' => null]);
        $this->assertSame(1, $res);

        $this->assertRecordCount(0);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_delete_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->delete('', ['test_string' => 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_delete_throws_exception_for_empty_conditions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty array');
        $this->better_wpdb->delete('test_table', []);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_delete_throws_exception_for_non_string_condition_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->delete('test_table', ['foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_delete_throws_exception_for_empty_string_condition_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');
        $this->better_wpdb->delete('test_table', ['', 'foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_delete_throws_exception_for_non_scalar_condition_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');
        $this->better_wpdb->delete('test_table', ['test_string' => new stdClass()]);
    }

    /**
     * @test
     */
    public function test_delete_is_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->delete('test_table', ['test_string' => 'foo']);

        $this->assertTrue(isset($logger->queries[0]));
        $this->assertCount(1, $logger->queries);

        $this->assertSame(
            'delete from `test_table` where `test_string` = ?',
            $logger->queries[0]->sql_with_placeholders
        );
        $this->assertSame(['foo'], $logger->queries[0]->bindings);
        $this->assertTrue($logger->queries[0]->end > $logger->queries[0]->start);
    }
}

<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use wpdb;

final class BetterWPDB_int_float_Test extends BetterWPDBTestCase
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
    public function integers_and_floats_are_returned_correctly(): void
    {
        $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10,
            'test_float' => 20.20,
            'test_bool' => 1
        ]);

        /** @var array{test_int: int, test_float:float, test_bool:int, test_string:string} $row */
        $row = $this->better_wpdb->selectRow('select * from test_table where id = 1', []);
        $this->assertSame('foo', $row['test_string']);
        // An INTEGER
        $this->assertSame(10, $row['test_int']);
        // A FLOAT
        $this->assertSame(20.20, $row['test_float']);
        $this->assertSame(1, $row['test_bool']);

        /** @var array{test_int: string, test_float:string, test_bool:string} $wpdb_result */
        $wpdb_result = $this->wpdb->get_row('select * from test_table where id = 1', 'ARRAY_A');
        // strings returned
        $this->assertSame('10', $wpdb_result['test_int']);
        $this->assertSame('20.20', $wpdb_result['test_float']);
        $this->assertSame('1', $wpdb_result['test_bool']);
    }

}
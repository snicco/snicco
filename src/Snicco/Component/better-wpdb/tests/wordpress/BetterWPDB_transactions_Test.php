<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use LogicException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;

final class BetterWPDB_transactions_Test extends BetterWPDBTestCase
{

    /**
     * @test
     */
    public function test_transactional_can_fail_and_roll_back(): void
    {
        $this->assertRecordCount(0);

        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db) {
                $res = $db->insert('test_table', ['test_string' => 'foo']);
                $this->assertSame(1, $res->affected_rows);

                $db->insert('test_table', ['test_string' => 'foo']);
            });
            $this->fail('No exception thrown for transaction.');
        } catch (RuntimeException $e) {
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

        $return = $this->better_wpdb->transactional(function (BetterWPDB $db) {
            $db->insert('test_table', ['test_string' => 'foo']);
            $db->insert('test_table', ['test_string' => 'bar']);

            return 'foobar';
        });

        $this->assertRecordCount(2);
        $this->assertSame('foobar', $return);
    }

    /**
     * @test
     */
    public function test_nested_transactions_throw_expection(): void
    {
        try {
            $this->better_wpdb->transactional(function (BetterWPDB $db) {
                $db->transactional(function () {
                    throw new RuntimeException('should never run.');
                });
            });
            $this->fail('No exception thrown for nested transaction.');
        } catch (LogicException $e) {
            $this->assertSame('Nested transactions are currently not supported.', $e->getMessage());
        }
    }

}
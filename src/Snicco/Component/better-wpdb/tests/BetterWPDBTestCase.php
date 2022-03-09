<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;

use function array_key_exists;

class BetterWPDBTestCase extends WPTestCase
{
    protected BetterWPDB $better_wpdb;

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

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->preparedQuery('DROP TABLE IF EXISTS test_table');
    }

    protected function assertRecordCount(int $expected): void
    {
        /** @var int $actual */
        $actual = $this->better_wpdb->selectValue('select count(*) from test_table', []);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string,scalar|null> $expected
     */
    protected function assertRecord(int $id, array $expected): void
    {
        $record = $this->better_wpdb->selectRow('select * from test_table where id = ?', [$id]);

        foreach ($expected as $name => $value) {
            if (!array_key_exists($name, $record)) {
                $this->fail("Record does not have key [$name].");
            }
            $this->assertSame($value, $record[$name]);
        }
    }
}

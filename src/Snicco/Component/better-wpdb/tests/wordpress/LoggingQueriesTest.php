<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use mysqli;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\ExecuteAndLog;
use Snicco\Component\BetterWPDB\MysqliFactory;
use Snicco\Component\BetterWPDB\QueryInfo;
use wpdb;

/**
 * @psalm-suppress PossiblyNullPropertyFetch
 * @psalm-suppress PossiblyUndefinedIntArrayOffset
 */
final class LoggingQueriesTest extends TestCase
{

    private BetterWPDB $better_wpdb;
    private wpdb $wpdb;
    /**
     * @var list<QueryInfo>
     */
    private array $logged_queries;
    private mysqli $mysqli;
    private BetterWPDB $logging_db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logged_queries = [];
        $this->wpdb = $GLOBALS['wpdb'];
        $this->mysqli = MysqliFactory::fromWpdbConnection();
        $this->wpdb->query('COMMIT');
        $this->wpdb->query('DROP TABLE IF EXISTS emails');
        $this->wpdb->query(
            'CREATE TABLE IF NOT EXISTS `emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(30) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  `money` FLOAT(9,2) UNSIGNED DEFAULT NULL,
  `test_int` INTEGER UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;'
        );
        $this->wpdb->insert('emails', ['email' => 'calvin@web.de']);
        $this->logging_db = new BetterWPDB(
            $this->mysqli, new ExecuteAndLog(function (QueryInfo $info): void {
                $this->logQuery($info);
            })
        );
    }

    protected function tearDown(): void
    {
        $this->wpdb->query('DROP TABLE IF EXISTS emails');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_preparedQuery_logs(): void
    {
        $res = $this->logging_db->preparedQuery('select email from emails where id = ?', [1]);

        $this->assertSame('calvin@web.de', $res->get_result()->fetch_object()->email);
        $this->assertNotEmpty($this->logged_queries);

        $info = $this->logged_queries[0];
        $this->assertSame('select email from emails where id = ?', $info->sql);
        $this->assertSame([1], $info->bindings);
    }

    /**
     * @test
     */
    public function test_transactional_logs_all_queries(): void
    {
        $this->logging_db->transactional(function (BetterWPDB $db) {
            $db->preparedQuery('select email from emails where id = ?', [1]);
            $db->preparedInsert('emails', ['email' => 'marlon@web.de']);
        });

        $this->assertNotEmpty($this->logged_queries);
        $this->assertCount(2, $this->logged_queries);

        $info = $this->logged_queries[0];
        $this->assertSame('select email from emails where id = ?', $info->sql);
        $this->assertSame([1], $info->bindings);

        $info = $this->logged_queries[1];
        $this->assertSame('insert into `emails` (`email`) values (?)', $info->sql);
        $this->assertSame(['marlon@web.de'], $info->bindings);
        $this->assertTrue($info->end > $info->start);

        $this->assertUserEmailIs('marlon@web.de', 2);
    }

    /**
     * @test
     */
    public function test_updateByPrimaryKeyLogs(): void
    {
        $this->logging_db->updateByPrimaryKey('emails', 1, ['money' => 100.10]);

        $this->assertNotEmpty($this->logged_queries);
        $info = $this->logged_queries[0];
        $this->assertSame('update `emails` set `money`= ? where `id`= ?', $info->sql);
        $this->assertSame([100.10, 1], $info->bindings);
    }

    private function logQuery(QueryInfo $info): void
    {
        $this->logged_queries[] = $info;
    }

    private function assertUserEmailIs(string $expected, int $id): void
    {
        $row = $this->logging_db->preparedSelect('select email from emails where id = ?', [$id]);
        $std = $row->fetch_object();
        $this->assertSame($expected, $std->email);
    }

    private function assertRecordCount(int $expected): void
    {
        $count = $this->logging_db->preparedQuery('select count(*) as count from emails', []);
        $count = $count->get_result()->fetch_object();
        $this->assertSame($expected, $count->count);
    }

    private function assertUserMoneyIs(float $expected, int $id): void
    {
        $row = $this->logging_db->preparedSelect('select money from emails where id = ?', [$id]);
        $std = $row->fetch_object();
        $this->assertSame($expected, $std->money);
    }


}
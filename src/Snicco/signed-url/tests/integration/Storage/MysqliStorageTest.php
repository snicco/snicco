<?php

declare(strict_types=1);

namespace Tests\SignedUrl\integration\Storage;

use mysqli;
use Codeception\TestCase\WPTestCase;
use Tests\SignedUrl\WithStorageTests;
use Snicco\SignedUrl\Storage\MysqliStorage;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

final class MysqliStorageTest extends WPTestCase
{
    
    use WithStorageTests;
    
    /**
     * @var mysqli
     */
    private $mysqli;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $this->mysqli->query("SET SESSION sql_mode='TRADITIONAL'");
        
        $this->createTables();
    }
    
    protected function tearDown() :void
    {
        $this->dropTables();
        parent::tearDown();
    }
    
    protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        return new MysqliStorage($this->mysqli, 'magic_links', $clock);
    }
    
    private function createTables()
    {
        $this->mysqli->query(
            'CREATE TABLE `magic_links` (
  `id` varchar(255) NOT NULL,
  `expires` int(11) unsigned NOT NULL,
  `left_usages` tinyint unsigned NOT NULL,
  `protects` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `link_expires_at_index` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; '
        );
    }
    
    private function dropTables()
    {
        $this->mysqli->query('DROP TABLE magic_links');
    }
    
}
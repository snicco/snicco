<?php

declare(strict_types=1);

namespace Snicco\Bridge\SingedUrlMysqli\Tests\integration;

use mysqli;
use PHPUnit\Framework\TestCase;
use Snicco\Bridge\SingedUrlMysqli\MysqliStorage;
use Snicco\Bridge\SingedUrlMysqli\Tests\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;

final class MysqliStorageTest extends TestCase
{
    
    use SignedUrlStorageTests;
    
    private mysqli $mysqli;
    
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
    
    protected function createStorage(SignedUrlClock $clock) :SignedUrlStorage
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
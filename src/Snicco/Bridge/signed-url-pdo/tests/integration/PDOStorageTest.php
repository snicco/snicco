<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Testing\integration\Storage;

use PDO;
use PDOException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\SignedUrl\Storage\PDOStorage;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;

final class PDOStorageTest extends WPTestCase
{
    
    use SignedUrlStorageTests;
    
    /**
     * @var PDO
     */
    private $pdo;
    
    protected function setUp() :void
    {
        parent::setUp();
        $host = DB_HOST;
        $db = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASSWORD;
        $charset = DB_CHARSET;
        
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
        $this->createTables();
    }
    
    protected function tearDown() :void
    {
        $this->dropTables();
        parent::tearDown();
    }
    
    protected function createStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        return new PDOStorage($this->pdo, 'magic_links', $clock);
    }
    
    private function createTables()
    {
        $this->pdo->query(
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
        $this->pdo->query('DROP TABLE magic_links');
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\SignedUrlWP\integration\Storage;

use Codeception\TestCase\WPTestCase;
use Snicco\SignedUrlWP\Storage\WPDBStorage;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;

/**
 * @group wordpress
 */
final class WPDBStorageTest extends WPTestCase
{
    
    use SignedUrlStorageTests;
    
    protected function setUp() :void
    {
        parent::setUp();
        global $wpdb;
        $this->db = $wpdb;
        $this->createTables();
    }
    
    protected function tearDown() :void
    {
        $this->dropTables();
        parent::tearDown();
    }
    
    protected function createStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        global $wpdb;
        
        return new WPDBStorage('wp_magic_links', $clock);
    }
    
    private function createTables()
    {
        $this->db->query(
            'CREATE TABLE `wp_magic_links` (
  `id` varchar(255) NOT NULL,
  `expires` int(11) unsigned NOT NULL,
  `left_usages` tinyint unsigned NOT NULL,
  `protects` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `link_expires_at_index` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; '
        );
    }
    
    private function dropTables()
    {
        $this->db->query('DROP TABLE wp_magic_links');
    }
    
}
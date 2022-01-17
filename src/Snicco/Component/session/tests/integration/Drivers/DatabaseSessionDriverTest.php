<?php

declare(strict_types=1);

namespace Tests\Session\integration\Drivers;

use Tests\Session\SessionDriverTest;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Testing\WithDatabaseExceptions;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\DatabaseSessionDriver;

final class DatabaseSessionDriverTest extends SessionDriverTest
{
    
    use WithDatabaseExceptions;
    
    protected function createDriver(SessionClock $clock) :SessionDriver
    {
        return new DatabaseSessionDriver($this->db, 'sessions', $clock);
    }
    
    protected function setUp() :void
    {
        parent::setUp();
        global $wpdb;
        $this->db = $wpdb;
        $this->createTables();
        $this->withDatabaseExceptions();
    }
    
    protected function tearDown() :void
    {
        $this->dropTables();
        parent::tearDown();
    }
    
    private function createTables()
    {
        $this->db->query(
            'CREATE TABLE `wp_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `data` text NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; '
        );
    }
    
    private function dropTables()
    {
        $this->db->query('DROP TABLE wp_sessions');
    }
    
}
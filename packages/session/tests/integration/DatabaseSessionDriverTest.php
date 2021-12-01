<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use wpdb;
use Mockery;
use Snicco\Support\WP;
use Snicco\Testing\Concerns\TravelsTime;
use Tests\Codeception\shared\FrameworkTestCase;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\Session\Drivers\DatabaseSessionDriver;

/** @todo test for getting all session for a user */
class DatabaseSessionDriverTest extends FrameworkTestCase
{
    
    use TravelsTime;
    
    private wpdb $db;
    
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
    
    /** @test */
    public function a_session_can_be_opened()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $this->assertTrue($handler->open('', ''));
    }
    
    /** @test */
    public function a_session_can_be_closed()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $this->assertTrue($handler->close());
    }
    
    /** @test */
    public function data_can_be_read_from_the_session()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('bar', $handler->read('foo'));
    }
    
    /** @test */
    public function reading_data_from_expired_sessions_returns_an_empty_string()
    {
        $handler = $this->newDataBaseSessionHandler(5);
        
        $handler->write('foo', 'bar');
        
        $this->travelIntoFuture(6);
        $this->assertSame('', $handler->read('foo'));
    }
    
    /** @test */
    public function testIsValid()
    {
        $handler = $this->newDataBaseSessionHandler(50);
        
        $handler->write('foo', 'bar');
        
        $this->assertFalse($handler->isValid('bar'));
        
        $this->travelIntoFuture(49);
        $this->assertTrue($handler->isValid('foo'));
        
        $this->travelIntoFuture(1);
        $this->assertFalse($handler->isValid('foo'));
    }
    
    /** @test */
    public function read_from_session_returns_empty_string_for_non_existing_id()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $handler->write('1', 'foo');
        
        $this->assertSame('', $handler->read('2'));
    }
    
    /** @test */
    public function data_can_be_read_from_an_almost_expired_session()
    {
        $handler = $this->newDataBaseSessionHandler(5);
        
        $handler->write('foo', 'bar');
        
        $this->travelIntoFuture(5);
        $this->assertSame('bar', $handler->read('foo'));
    }
    
    /** @test */
    public function an_existing_session_can_be_updated()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $handler->write('foo', 'bar');
        $handler->write('foo', 'baz');
        
        $this->assertSame('baz', $handler->read('foo'));
    }
    
    /** @test */
    public function the_user_id_is_included_in_the_session_record()
    {
        WP::shouldReceive('userId')->once()->andReturn(10);
        
        $handler = $this->newDataBaseSessionHandler();
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('bar', $handler->read('foo'));
        
        $this->assertSame(10, $this->getUserId('foo'));
        
        WP::reset();
        Mockery::close();
    }
    
    /**
     * @test
     * NOTE: Requires a Psr15 Middleware that sets the ip_address attribute.
     * @see https://github.com/akrabat/ip-address-middleware/
     */
    public function the_ip_address_is_included_in_the_session_record()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $request = TestRequest::from('GET', 'foo');
        
        $handler->setRequest($request->withAttribute('ip_address', '1234'));
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('1234', $this->getIp('foo'));
    }
    
    /** @test */
    public function the_user_agent_is_included_in_the_session_record()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $request = TestRequest::from('GET', 'foo');
        
        $handler->setRequest($request->withAddedHeader('User-Agent', 'calvin'));
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('calvin', $this->getUserAgent('foo'));
    }
    
    /** @test */
    public function a_session_can_be_destroyed()
    {
        $handler = $this->newDataBaseSessionHandler();
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('bar', $handler->read('foo'));
        
        $this->assertTrue($handler->destroy('foo'));
        
        $this->assertSame('', $handler->read('foo'));
    }
    
    /** @test */
    public function garbage_collection_works_for_old_sessions()
    {
        $handler = $this->newDataBaseSessionHandler(5);
        
        $handler->write('foo', 'bar');
        
        // 300s = 5 min
        $this->assertTrue($handler->gc(5));
        $this->assertSame('bar', $handler->read('foo'));
        
        $this->travelIntoFuture(1);
        $handler->write('bar', 'baz');
        
        $this->travelIntoFuture(5);
        
        $this->assertTrue($handler->gc(6));
        
        // first session is expired
        $this->assertSame('', $handler->read('foo'));
        
        // second session is valid by one seconds.
        $this->assertSame('baz', $handler->read('bar'));
    }
    
    private function newDataBaseSessionHandler(int $lifetime = 10) :DatabaseSessionDriver
    {
        return new DatabaseSessionDriver($this->db, 'sessions', $lifetime);
    }
    
    private function getUserId(string $id)
    {
        return (int) $this->db->get_var("SELECT user_id FROM wp_sessions WHERE id = '{$id}'");
    }
    
    private function getIp(string $id)
    {
        return (string) $this->db->get_var("SELECT ip_address FROM wp_sessions WHERE id = '{$id}'");
    }
    
    private function getUserAgent(string $id)
    {
        return (string) $this->db->get_var("SELECT user_agent FROM wp_sessions WHERE id = '{$id}'");
    }
    
    private function createTables()
    {
        $this->db->query(
            "CREATE TABLE `wp_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; "
        );
    }
    
    private function dropTables()
    {
        $this->db->query("DROP TABLE wp_sessions");
    }
    
}

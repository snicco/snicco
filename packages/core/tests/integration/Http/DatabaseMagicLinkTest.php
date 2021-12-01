<?php

declare(strict_types=1);

namespace Tests\Core\integration\Http;

use wpdb;
use Snicco\Support\Carbon;
use Snicco\Http\DatabaseMagicLink;
use Tests\Codeception\shared\FrameworkTestCase;
use Tests\Core\fixtures\TestDoubles\TestRequest;

class DatabaseMagicLinkTest extends FrameworkTestCase
{
    
    private wpdb              $db;
    private DatabaseMagicLink $magic_link;
    private Carbon            $expires;
    private string            $table;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
        global $wpdb;
        $this->db = $wpdb;
        $this->createTables();
        $this->table = 'wp_magic_links';
        $this->magic_link = new DatabaseMagicLink('magic_links', [0, 100]);
        $this->magic_link->setAppKey(TEST_APP_KEY);
        $this->expires = Carbon::now();
    }
    
    protected function tearDown() :void
    {
        $this->dropTables();
        parent::tearDown();
    }
    
    /** @test */
    public function a_magic_link_gets_stored()
    {
        $signature = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        
        $this->assertSeeLink(md5($signature));
    }
    
    /** @test */
    public function magic_link_signatures_are_not_stored_as_plain_text()
    {
        $signature = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        
        $this->assertNotSeeLink($signature);
    }
    
    /** @test */
    public function a_magic_link_can_be_invalidated()
    {
        $signature = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        
        $this->assertSeeLink(md5($signature));
        
        $this->magic_link->invalidate('/foo?signature='.$signature);
        
        $this->assertNotSeeLink(md5($signature));
    }
    
    /** @test */
    public function magic_link_usage_can_be_checked()
    {
        $expires = $this->expires->timestamp;
        $signature = $this->magic_link->create('/foo', $expires, $this->request);
        
        $request = TestRequest::fromFullUrl(
            "GET",
            "https://foo.com/foo?expires=$expires&signature=$signature"
        );
        
        $this->assertTrue($this->magic_link->notUsed($request));
        
        $this->magic_link->invalidate($request->fullUrl());
        
        $this->assertFalse($this->magic_link->notUsed($request));
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $signature1 = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        $signature2 = $this->magic_link->create(
            '/bar',
            $this->expires->addSeconds(100)->getTimestamp(),
            $this->request
        );
        
        Carbon::setTestNow(Carbon::now()->addSeconds(9));
        $this->magic_link->gc();
        
        $this->assertNotSeeLink(md5($signature1));
        $this->assertSeeLink(md5($signature2));
        
        Carbon::setTestNow();
    }
    
    /** @test */
    public function a_magic_link_with_the_same_signature_is_not_stored_twice()
    {
        $signature = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        $signature = $this->magic_link->create('/foo', $this->expires->timestamp, $this->request);
        
        $this->db->query('COMMIT');
        
        $query = $this->db->prepare(
            "SELECT COUNT(*) FROM $this->table WHERE signature = %s",
            md5($signature)
        );
        $count = $this->db->get_var($query);
        
        $this->assertSame('1', $count, 'An identical magic link got created twice.');
    }
    
    private function assertSeeLink(string $signature)
    {
        $query = $this->db->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE signature = %s LIMIT 1)",
            $signature
        );
        
        $exists = $this->db->get_var($query);
        
        $result = (is_string($exists) && $exists === '1');
        
        $this->assertTrue($result);
    }
    
    private function assertNotSeeLink(string $signature)
    {
        $query = $this->db->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE signature = %s LIMIT 1)",
            $signature
        );
        
        $exists = $this->db->get_var($query);
        
        $result = (is_string($exists) && $exists === '1');
        
        $this->assertFalse($result);
    }
    
    private function createTables()
    {
        $this->db->query(
            "CREATE TABLE `wp_magic_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expires` int NOT NULL,
  `signature` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `sessions_last_activity_index` (`expires`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8;"
        );
    }
    
    private function dropTables()
    {
        $this->db->query("DROP TABLE wp_magic_links");
    }
    
}

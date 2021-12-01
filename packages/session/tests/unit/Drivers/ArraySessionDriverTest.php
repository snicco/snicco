<?php

declare(strict_types=1);

namespace Tests\Session\unit\Drivers;

use Mockery;
use stdClass;
use Snicco\Support\WP;
use Tests\Codeception\shared\UnitTest;
use Snicco\Testing\Concerns\TravelsTime;
use Snicco\Session\Drivers\ArraySessionDriver;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class ArraySessionDriverTest extends UnitTest
{
    
    use TravelsTime;
    use CreateDefaultWpApiMocks;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->backToPresent();
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function a_session_can_be_opened()
    {
        $handler = new ArraySessionDriver(10);
        
        $this->assertTrue($handler->open('', ''));
    }
    
    /** @test */
    public function a_session_can_be_closed()
    {
        $handler = new ArraySessionDriver(10);
        
        $this->assertTrue($handler->close());
    }
    
    /** @test */
    public function read_from_session_returns_empty_string_for_non_existing_id()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write(1, 'foo');
        
        $this->assertSame('', $handler->read(2));
    }
    
    /** @test */
    public function data_can_be_read_from_the_session()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        
        $this->assertSame('bar', $handler->read('foo'));
    }
    
    /** @test */
    public function data_can_be_read_from_an_almost_expired_session()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        
        $this->travelIntoFuture(10);
        $this->assertSame('bar', $handler->read('foo'));
    }
    
    /** @test */
    public function reading_data_from_expired_sessions_returns_an_empty_string()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        
        $this->travelIntoFuture(11);
        $this->assertSame('', $handler->read('foo'));
    }
    
    /** @test */
    public function data_can_be_written_to_the_session()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        $handler->write('foo', 'baz');
        
        $this->assertSame('baz', $handler->read('foo'));
    }
    
    /** @test */
    public function a_session_can_be_destroyed()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        
        $this->assertTrue($handler->destroy('foo'));
        
        $this->assertSame('', $handler->read('foo'));
    }
    
    /** @test */
    public function garbage_collection_works_for_old_sessions()
    {
        $handler = new ArraySessionDriver(10);
        
        $handler->write('foo', 'bar');
        $this->assertTrue($handler->gc(10));
        $this->assertSame('bar', $handler->read('foo'));
        
        $this->travelIntoFuture(1);
        $handler->write('bar', 'baz');
        
        $this->travelIntoFuture(10);
        $this->assertTrue($handler->gc(300));
        $this->assertSame('', $handler->read('foo'));
        $this->assertSame('baz', $handler->read('bar'));
    }
    
    /** @test */
    public function all_session_for_a_given_user_id_can_be_retrieved()
    {
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        $handler = new ArraySessionDriver(10);
        $handler->setRequest($this->newRequest());
        $handler->write('foo', 'bar');
        
        $handler->setRequest($this->newRequest());
        $handler->write('bar', 'baz');
        
        WP::shouldReceive('userId')->andReturn(2);
        $handler->setRequest($this->newRequest());
        $handler->write('biz', 'bam');
        
        $sessions = $handler->getAllByUserId(1);
        
        $this->assertContainsOnlyInstancesOf(stdClass::class, $sessions);
        $this->assertCount(2, $sessions);
        $this->assertSame(1, $sessions[0]->user_id);
        $this->assertSame(1, $sessions[1]->user_id);
        $this->assertSame('foo', $sessions[0]->id);
        $this->assertSame('bar', $sessions[1]->id);
    }
    
    /** @test */
    public function all_sessions_but_the_one_with_the_provided_token_can_be_destroyed_for_the_user()
    {
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        $handler = new ArraySessionDriver(10);
        $handler->setRequest($this->newRequest());
        $handler->write('foo', 'bar');
        $handler->setRequest($this->newRequest());
        $handler->write('bar', 'baz');
        
        WP::shouldReceive('userId')->andReturn(2);
        $handler->setRequest($this->newRequest());
        $handler->write('biz', 'bam');
        
        $handler->destroyOthersForUser('foo', 1);
        
        $this->assertCount(2, $handler->all());
        
        $this->assertSame('', $handler->read('bar'));
        $this->assertSame('bam', $handler->read('biz'));
    }
    
    /** @test */
    public function all_sessions_for_a_user_can_be_destroyed()
    {
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        $handler = new ArraySessionDriver(10);
        $handler->setRequest($this->newRequest());
        $handler->write('foo', 'bar');
        $handler->setRequest($this->newRequest());
        $handler->write('bar', 'baz');
        
        WP::shouldReceive('userId')->andReturn(2);
        
        $handler->setRequest($this->newRequest());
        $handler->write('biz', 'bam');
        
        $handler->destroyAllForUser(1);
        
        $this->assertCount(1, $handler->all());
        
        $this->assertSame('', $handler->read('foo'));
        $this->assertSame('', $handler->read('bar'));
        $this->assertSame('bam', $handler->read('biz'));
    }
    
    /** @test */
    public function all_sessions_can_be_destroyed()
    {
        $handler = new ArraySessionDriver(10);
        $handler->setRequest($this->newRequest());
        $handler->write('foo', 'bar');
        $handler->setRequest($this->newRequest());
        $handler->write('bar', 'baz');
        
        WP::shouldReceive('userId')->andReturn(2);
        
        $handler->setRequest($this->newRequest());
        $handler->write('biz', 'bam');
        
        $handler->destroyAll();
        
        $this->assertSame([], $handler->all());
    }
    
    private function newRequest()
    {
        return TestRequest::from('GET', 'foo');
    }
    
}

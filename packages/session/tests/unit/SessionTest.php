<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use Mockery;
use Snicco\Support\WP;
use Snicco\Support\Carbon;
use Snicco\Session\Session;
use SessionHandlerInterface;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\Drivers\ArraySessionDriver;
use Tests\Codeception\shared\helpers\HashesSessionIds;

use function serialize;
use function unserialize;

class SessionTest extends UnitTest
{
    
    use HashesSessionIds;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        Carbon::setTestNow();
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
    }
    
    protected function tearDown() :void
    {
        Carbon::setTestNow();
        WP::reset();
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function a_session_is_loaded_from_the_handler()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('baz', $session->get('not-present', 'baz'));
        $this->assertTrue($session->has('foo'));
        $this->assertFalse($session->has('bar'));
        $this->assertTrue($session->isStarted());
    }
    
    /** @test */
    public function provided_a_semanticly_correct_session_id_that_does_not_exist_in_the_driver_regenerates_the_id()
    {
        $handler = $this->newArrayHandler();
        $handler->write($this->getSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->anotherSessionId());
        
        $this->assertNotSame($session->getId(), $this->anotherSessionId());
    }
    
    /** @test */
    public function the_last_activity_gets_added_when_saving_the_session()
    {
        $session = $this->newSessionStore();
        $session->start();
        $session->save();
        
        $this->assertSame(Carbon::now()->getTimestamp(), $session->lastActivity());
    }
    
    /** @test */
    public function rotate_time_can_be_set_and_retrieved()
    {
        $session = $this->newSessionStore();
        $session->setNextRotation(30);
        
        $this->assertSame(Carbon::now()->addSeconds(30)->getTimestamp(), $session->rotationDueAt());
    }
    
    /** @test */
    public function absolute_expiration_time_can_be_set_and_retrieved()
    {
        $session = $this->newSessionStore();
        $session->setAbsoluteTimeout(300);
        
        $this->assertSame(
            Carbon::now()->addSeconds(300)
                  ->getTimestamp(),
            $session->absoluteTimeout()
        );
    }
    
    /** @test */
    public function session_attributes_are_merged_with_handler_attributes()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write(
            $this->hashedSessionId(),
            serialize([
                
                'foo' => [
                    'bar',
                    'baz' => [
                        'biz' => 'boom',
                    ],
                ],
            
            ])
        );
        
        $session = $this->newSessionStore($handler);
        $session->put('foo.baz.biz', 'bam');
        $session->put('foo.biz', 'boom');
        $session->start($this->getSessionId());
        
        $session->forget('_token');
        
        $this->assertEquals([
            
            'foo' => [
                'bar',
                'baz' => [
                    'biz' => 'boom',
                ],
                'biz' => 'boom',
            ],
        
        ], $session->all());
    }
    
    /** @test */
    public function the_session_has_no_attributes_if_the_handler_doesnt()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->getSessionId(), serialize([]));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $session->forget('_token');
        
        $this->assertSame([], $session->all());
    }
    
    /** @test */
    public function a_session_id_can_be_migrated_without_destroying_the_old_sessions()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $old_id = $session->getId();
        
        $this->assertTrue($session->regenerate(false));
        $new_id = $session->getId();
        
        $this->assertNotEquals($old_id, $new_id);
        $this->assertNotEmpty($handler->read($this->hash($old_id)));
    }
    
    /** @test */
    public function regenerate_is_an_alias_for_migrate()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $old_id = $session->getId();
        
        $this->assertTrue($session->regenerate());
        $session->save();
        $new_id = $session->getId();
        
        $this->assertNotEquals($old_id, $new_id);
        $this->assertEmpty($handler->read($this->hash($old_id)));
        $this->assertNotEmpty($handler->read($this->hash($new_id)));
    }
    
    /** @test */
    public function a_session_id_can_be_migrated_and_destroy_the_session_attributes()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->getSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $old_id = $session->getId();
        
        $this->assertTrue($session->regenerate());
        $new_id = $session->getId();
        
        $this->assertNotEquals($old_id, $new_id);
        $this->assertEmpty($handler->read($old_id));
    }
    
    /** @test */
    public function regenerate_can_also_destroy_old_session_data()
    {
        $handler = $this->newArrayHandler(10);
        $handler->write($this->getSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $old_id = $session->getId();
        
        $this->assertTrue($session->regenerate(true));
        $new_id = $session->getId();
        
        $this->assertNotEquals($old_id, $new_id);
        $this->assertEmpty($handler->read($old_id));
    }
    
    /** @test */
    public function a_session_is_properly_saved()
    {
        $session = $this->newSessionStore();
        $session->start($this->getSessionId());
        
        $session->put('_flash.old', 'foo');
        $session->put('_flash.new', 'bar');
        $session->put('baz', 'biz');
        
        $this->assertEmpty($session->getDriver()->read($this->hash($session->getId())));
        
        $session->forget('_token');
        
        $session->save();
        
        $this->assertEmpty($session->get('_flash.new'));
        $this->assertSame('bar', $session->get('_flash.old'));
        
        $this->assertEquals(
            [
                
                'baz' => 'biz',
                '_flash' => [
                    'old' => 'bar',
                    'new' => [],
                ],
                '_last_activity' => time(),
            
            ],
            unserialize($session->getDriver()->read($this->hash($session->getId())))
        );
    }
    
    /** @test */
    public function a_session_is_saved_when_the_session_id_changed()
    {
        $handler = $this->newArrayHandler();
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $session->regenerate();
        $new_id = $session->getId();
        
        $session->forget('_token');
        
        $session->save();
        
        $this->assertEquals([
            'foo' => 'bar',
            '_flash' => [
                'old' => [],
                'new' => [],
            ],
            '_last_activity' => time(),
        
        ], unserialize($handler->read($this->hash($new_id))));
    }
    
    /** @test */
    public function all_session_attributes_can_be_retrieved()
    {
        $handler = $this->newArrayHandler();
        $handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $session->forget('_token');
        $this->assertSame(['foo' => 'bar',], $session->all());
    }
    
    /** @test */
    public function only_a_partial_of_the_session_attributes_can_be_retrieved()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $this->assertEquals(['foo' => 'bar', 'baz' => 'biz'], $session->all());
        $this->assertEquals(['baz' => 'biz'], $session->only(['baz']));
    }
    
    /** @test */
    public function key_existence_be_checked()
    {
        $session = $this->newSessionStore();
        
        $session->put('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
        
        $session->put('baz', null);
        $session->put('hulk', ['one' => true]);
        
        $this->assertTrue($session->exists('baz'));
        $this->assertTrue($session->exists(['foo', 'baz']));
        $this->assertTrue($session->exists(['hulk.one']));
        
        $this->assertFalse($session->exists(['foo', 'baz', 'bogus']));
        $this->assertFalse($session->exists(['hulk.two']));
        $this->assertFalse($session->exists('bogus'));
    }
    
    /** @test */
    public function it_can_be_checked_if_keys_are_missing()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', null);
        $session->put('hulk', ['one' => true]);
        
        $this->assertTrue($session->missing('bogus'));
        $this->assertTrue($session->missing(['foo', 'baz', 'bogus']));
        $this->assertTrue($session->missing(['hulk.two']));
        
        $this->assertFalse($session->missing('foo'));
        $this->assertFalse($session->missing('baz'));
        $this->assertFalse($session->missing(['foo', 'baz']));
        $this->assertFalse($session->missing(['hulk.one']));
    }
    
    /** @test */
    public function it_can_be_checked_that_keys_are_present_and_not_null()
    {
        $session = $this->newSessionStore();
        $session->put('foo', null);
        $session->put('bar', 'baz');
        
        $this->assertTrue($session->has('bar'));
        $this->assertFalse($session->has('foo'));
    }
    
    /** @test */
    public function a_specific_key_can_be_retrieved_with_optional_default_value()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        
        $this->assertSame('bar', $session->get('foo', 'default'));
        $this->assertSame('default', $session->get('boo', 'default'));
    }
    
    /** @test */
    public function a_key_can_be_pulled_out_of_the_session_and_is_not_present_anymore_after()
    {
        $handler = $this->newArrayHandler();
        $handler->write(
            $this->hashedSessionId(),
            serialize([
                'foo' => 'bar',
                'baz' => 'biz',
            ])
        );
        $session = $this->newSessionStore($handler);
        
        $session->start($this->getSessionId());
        
        $session->forget('_token');
        
        $this->assertSame(['foo' => 'bar', 'baz' => 'biz'], $session->all());
        $this->assertSame('biz', $session->pull('baz'));
        
        $this->assertSame(['foo' => 'bar'], $session->all());
        
        $this->assertSame('default', $session->pull('bogus', 'default'));
        
        $this->assertSame(['foo' => 'bar'], $session->all());
    }
    
    /** @test */
    public function it_can_be_checked_if_old_input_exists()
    {
        $session = $this->newSessionStore();
        
        $this->assertFalse($session->hasOldInput());
        
        $session->put('_old_input', ['foo' => 'bar', 'bar' => 'baz', 'boo' => null]);
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertTrue($session->hasOldInput('bar'));
        $this->assertFalse($session->hasOldInput('biz'));
        $this->assertFalse($session->hasOldInput('boo'));
    }
    
    /** @test */
    public function old_put_can_be_retrieved()
    {
        $session = $this->newSessionStore();
        
        $this->assertSame([], $session->getOldInput());
        
        $session->put('_old_input', ['foo' => 'bar', 'bar' => 'baz', 'boo' => null]);
        
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'boo' => null,
        ], $session->getOldInput());
        
        $this->assertSame('bar', $session->getOldInput('foo'));
        $this->assertSame('baz', $session->getOldInput('bar'));
        $this->assertSame(null, $session->getOldInput('boo'));
        
        $this->assertSame(null, $session->getOldInput('boo', 'default'));
        $this->assertSame('default', $session->getOldInput('bogus', 'default'));
    }
    
    /** @test */
    public function session_attributes_can_be_replaced()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->replace(['foo' => 'baz']);
        $this->assertSame('baz', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
    }
    
    /** @test */
    public function a_key_can_be_remembered_and_stores_the_default_value_if_not_present()
    {
        $session = $this->newSessionStore();
        
        $result = $session->remember('foo', function () {
            return 'bar';
        });
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('bar', $result);
        
        $session->put('baz', 'biz');
        
        $result = $session->remember('baz', function () {
            $this->fail('This should not have been called');
        });
        
        $this->assertSame('biz', $result);
    }
    
    /** @test */
    public function a_value_can_be_pushed_onto_a_array_value()
    {
        $session = $this->newSessionStore();
        
        $session->put('foo', ['bar']);
        $session->push('foo', 'bar');
        $session->push('foo', ['baz' => 'biz']);
        
        $this->assertSame(['bar', 'bar', ['baz' => 'biz']], $session->get('foo'));
    }
    
    /** @test */
    public function an_integer_value_can_be_incremented()
    {
        $session = $this->newSessionStore();
        
        $session->put('foo', 5);
        $foo = $session->increment('foo');
        $this->assertEquals(6, $foo);
        $this->assertEquals(6, $session->get('foo'));
        
        $foo = $session->increment('foo', 4);
        $this->assertEquals(10, $foo);
        $this->assertEquals(10, $session->get('foo'));
        
        $this->assertEquals(0, $session->get('bar'));
        $session->increment('bar');
        $this->assertEquals(1, $session->get('bar'));
    }
    
    /** @test */
    public function an_integer_value_can_be_decremented()
    {
        $session = $this->newSessionStore();
        
        $session->put('foo', 5);
        $foo = $session->decrement('foo');
        $this->assertEquals(4, $foo);
        $this->assertEquals(4, $session->get('foo'));
        
        $foo = $session->decrement('foo', 4);
        $this->assertEquals(0, $foo);
        $this->assertEquals(0, $session->get('foo'));
        
        $this->assertEquals(0, $session->get('bar'));
        $session->decrement('bar');
        $this->assertEquals(-1, $session->get('bar'));
    }
    
    /** @test */
    public function a_value_can_be_flashed_for_the_next_request()
    {
        $session = $this->newSessionStore();
        $session->start($this->getSessionId());
        $session->flash('foo', 'bar');
        $session->flash('bar', 0);
        $session->flash('baz');
        
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame(0, $session->get('bar'));
        $this->assertSame(true, $session->get('baz'));
        
        $session->save();
        
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));
        
        $session->save();
        
        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }
    
    /** @test */
    public function data_can_be_flashed_to_the_current_request()
    {
        $session = $this->newSessionStore();
        $session->start($this->getSessionId());
        $session->now('foo', 'bar');
        $session->now('bar', 0);
        
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));
        
        $session->save();
        
        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }
    
    /** @test */
    public function input_can_be_flashed_to_the_current_request()
    {
        $session = $this->newSessionStore();
        $session->start($this->getSessionId());
        $session->flashInputNow(['foo' => 'bar']);
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->getOldInput('foo'));
        
        $session->save();
        
        $this->assertFalse($session->hasOldInput('foo'));
    }
    
    /** @test */
    public function session_data_can_be_reflashed()
    {
        $session = $this->newSessionStore();
        $session->flash('foo', 'bar');
        $session->put('_flash.old', ['foo']);
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }
    
    /** @test */
    public function reflash_can_be_combined_with_now()
    {
        $session = $this->newSessionStore();
        $session->now('foo', 'bar');
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }
    
    /** @test */
    public function old_input_can_be_flashed()
    {
        $session = $this->newSessionStore();
        $session->start($this->getSessionId());
        $session->put('boom', 'baz');
        $session->flashInput(['foo' => 'bar', 'bar' => 0]);
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->getOldInput('foo'));
        $this->assertEquals(0, $session->getOldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));
        
        $session->save();
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->getOldInput('foo'));
        $this->assertEquals(0, $session->getOldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));
    }
    
    /** @test */
    public function flashed_data_can_be_merged()
    {
        $session = $this->newSessionStore();
        $session->flash('foo', 'bar');
        $session->put('fu', 'baz');
        $session->put('_flash.old', ['qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('fu', $session->get('_flash.new')));
        $session->keep(['fu', 'qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertNotFalse(array_search('fu', $session->get('_flash.new')));
        $this->assertNotFalse(array_search('qu', $session->get('_flash.new')));
        $this->assertFalse(array_search('qu', $session->get('_flash.old')));
    }
    
    /** @test */
    public function remove_is_an_alias_for_pull()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        
        $pulled = $session->remove('foo');
        
        $this->assertSame('bar', $pulled);
        $this->assertSame('biz', $session->get('baz'));
        $this->assertFalse($session->has('foo'));
    }
    
    /** @test */
    public function attributes_can_be_forgotten_by_key()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->put('boo', ['boom', 'bang' => 'bam']);
        
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
        $this->assertSame(['boom', 'bang' => 'bam'], $session->get('boo'));
        $this->assertSame('bam', $session->get('boo.bang'));
        
        $session->forget('foo');
        $session->forget('boo.bang');
        
        $this->assertFalse($session->exists('foo'));
        $this->assertTrue($session->exists('baz'));
        $this->assertTrue($session->exists('boo'));
        
        $this->assertSame(['boom'], $session->get('boo'));
    }
    
    /** @test */
    public function the_entire_session_can_be_flushed()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->put('boo', ['boom', 'bang' => 'bam']);
        
        $session->flush();
        
        $this->assertSame([], $session->all());
    }
    
    /** @test */
    public function the_entire_session_can_be_invalidated()
    {
        $session = $this->newSessionStore();
        $session->start('bogus');
        $old_id = $session->getId();
        
        $session->put('foo', 'bar');
        $this->assertGreaterThan(0, count($session->all()));
        
        $session->save();
        
        $this->assertArrayHasKey(
            'foo',
            unserialize(
                $session->getDriver()
                        ->read($this->hash($old_id))
            )
        );
        
        $this->assertTrue($session->invalidate());
        
        $this->assertNotEquals($old_id, $session->getId());
        $session->forget('_token');
        $this->assertCount(0, $session->all());
        $this->assertEquals('', $session->getDriver()->read($this->hash($old_id)));
    }
    
    /** @test */
    public function its_not_possible_to_set_an_invalid_session_id()
    {
        $session = $this->newSessionStore();
        $session->getDriver()->write($this->hashedSessionId(), serialize(['foo' => 'bar']));
        $session->start($this->getSessionId());
        $this->assertTrue($session->isValidId($session->getId()));
        
        $session->setId($this->anotherSessionId());
        $this->assertNotSame($this->anotherSessionId(), $session->getId());
        $this->assertFalse($session->isValidId($this->anotherSessionId()));
    }
    
    /** @test */
    public function the_previous_url_can_be_set()
    {
        $session = $this->newSessionStore();
        $this->assertEquals(null, $session->getPreviousUrl(null));
        
        $session->setPreviousUrl('https.//foo.com');
        $this->assertSame('https.//foo.com', $session->getPreviousUrl());
    }
    
    /** @test */
    public function changes_in_the_session_can_be_detected()
    {
        $handler = $this->newArrayHandler();
        $handler->write($this->getSessionId(), serialize(['foo' => 'bar']));
        $session = $this->newSessionStore($handler);
        $session->start($this->getSessionId());
        
        $session->put('bar', 'baz');
        
        $this->assertTrue($session->wasChanged());
        
        $session->remove('bar');
        
        $this->assertFalse($session->wasChanged());
    }
    
    /** @test */
    public function allow_routes_can_be_set_for_a_defined_time()
    {
        $session = $this->newSessionStore();
        $session->start();
        
        $session->allowAccessToRoute(
            '/protected/route',
            Carbon::now()->addSeconds(300)
                  ->getTimestamp()
        );
        $this->assertTrue($session->canAccessRoute('/protected/route'));
        
        $this->assertNotSame([], $session->get('_allow_routes'));
        
        $session->allowAccessToRoute(
            '/other/route',
            $ts = Carbon::now()->addSeconds(300)
                        ->getTimestamp()
        );
        Carbon::setTestNow(Carbon::now()->addSeconds(301));
        $this->assertFalse($session->canAccessRoute('/protected/route'));
        
        // The other route did not get deleted.
        $this->assertSame(['/other/route' => $ts], $session->get('_allow_routes'));
        
        Carbon::setTestNow();
    }
    
    /** @test */
    public function session_errors_can_be_set()
    {
        $session = $this->newSessionStore();
        $session->start();
        
        $session->withErrors(['foo' => 'bar']);
        
        $errors = $session->errors();
        $this->assertSame('bar', $errors->first('foo'));
        
        $session->save();
        $session->save();
        
        $this->assertSame('', $session->errors()->first('foo'));
    }
    
    /** @test */
    public function new_sessions_will_have_a_csrf_token()
    {
        $session = $this->newSessionStore();
        
        $this->assertFalse($session->has('_token'));
        
        $session->start();
        
        $this->assertTrue($session->has('_token'));
        $this->assertEquals(40, strlen($session->csrfToken()));
    }
    
    /** @test */
    public function csrf_tokens_can_be_regenerated()
    {
        $session = $this->newSessionStore();
        $session->start();
        
        $old = $session->csrfToken();
        $session->regenerateCsrfToken();
        
        $this->assertNotSame($old, $session->csrfToken());
    }
    
    /** @test */
    public function testBoolean()
    {
        $session = $this->newSessionStore();
        $session->put('foo', 1);
        $session->put('bar', '1');
        $session->put('baz', 'on');
        $session->put('biz', 'yes');
        $session->put('boo', true);
        $session->put('bam', 'true');
        $session->put('bogus', 'bogus');
        $session->put('false_word', 'false');
        $session->put('off', 'off');
        $session->put('no', 'no');
        $session->put('false_bool', false);
        
        $this->assertTrue($session->boolean('foo'));
        $this->assertTrue($session->boolean('bar'));
        $this->assertTrue($session->boolean('baz'));
        $this->assertTrue($session->boolean('biz'));
        $this->assertTrue($session->boolean('boo'));
        $this->assertTrue($session->boolean('bam'));
        $this->assertFalse($session->boolean('bogus'));
        $this->assertFalse($session->boolean('false_word'));
        $this->assertFalse($session->boolean('off'));
        $this->assertFalse($session->boolean('no'));
        $this->assertFalse($session->boolean('false_bool'));
    }
    
    /** @test */
    public function test_was_saved()
    {
        $session = $this->newSessionStore();
        $session->start();
        
        $this->assertFalse($session->wasSaved());
        
        $session->save();
        
        $this->assertTrue($session->wasSaved());
    }
    
    private function newArrayHandler(int $minutes = 10) :ArraySessionDriver
    {
        return new ArraySessionDriver($minutes);
    }
    
    private function newSessionStore(SessionHandlerInterface $handler = null) :Session
    {
        return new Session(
            $handler ?? new ArraySessionDriver(10),
        );
    }
    
}
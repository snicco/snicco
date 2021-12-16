<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Mockery;
use DateTimeImmutable;
use Snicco\Session\Session;
use Codeception\TestCase\WPTestCase;
use Snicco\Session\Events\SessionRotated;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Exceptions\SessionIsLocked;

class SessionTest extends WPTestCase
{
    
    /** @test */
    public function the_session_is_locked_after_saving()
    {
        $session = $this->newSession();
        
        $session->put('foo', 'bar');
        
        $session->saveUsing(new ArraySessionDriver(), new DateTimeImmutable());
        
        $this->expectException(SessionIsLocked::class);
        
        $session->put('baz', 'biz');
    }
    
    /** @test */
    public function all_does_not_include_internal_timestamps()
    {
        $session = $this->newSession();
        
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        
        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'biz',
            ],
            $session->all()
        );
    }
    
    /** @test */
    public function testOnly()
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $this->assertEquals(['baz' => 'biz'], $session->only(['baz']));
    }
    
    /** @test */
    public function testExists()
    {
        $session = $this->newSession();
        
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
    public function testMissing()
    {
        $session = $this->newSession();
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
    public function testHas()
    {
        $session = $this->newSession();
        $session->put('foo', null);
        $session->put('bar', 'baz');
        
        $this->assertTrue($session->has('bar'));
        $this->assertFalse($session->has('foo'));
    }
    
    /** @test */
    public function testGet()
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        
        $this->assertSame('bar', $session->get('foo', 'default'));
        $this->assertSame('default', $session->get('boo', 'default'));
    }
    
    /** @test */
    public function testPull()
    {
        $session = $this->newSession(SessionId::createFresh()->asString(), [
            'foo' => 'bar',
            'baz' => 'biz',
        ]);
        
        $this->assertSame(['foo' => 'bar', 'baz' => 'biz'], $session->only(['foo', 'baz']));
        $this->assertSame('biz', $session->pull('baz'));
        
        $this->assertSame(['foo' => 'bar'], $session->all());
        
        $this->assertSame('default', $session->pull('bogus', 'default'));
        
        $this->assertSame(['foo' => 'bar'], $session->all());
    }
    
    /** @test */
    public function testHasOldInput()
    {
        $session = $this->newSession();
        
        $this->assertFalse($session->hasOldInput());
        
        $session->put('_old_input', ['foo' => 'bar', 'bar' => 'baz', 'boo' => null]);
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertTrue($session->hasOldInput('bar'));
        $this->assertFalse($session->hasOldInput('biz'));
        $this->assertFalse($session->hasOldInput('boo'));
    }
    
    /** @test */
    public function testGetOldInput()
    {
        $session = $this->newSession();
        
        $this->assertSame([], $session->oldInput());
        
        $session->put('_old_input', ['foo' => 'bar', 'bar' => 'baz', 'boo' => null]);
        
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'boo' => null,
        ], $session->oldInput());
        
        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertSame('baz', $session->oldInput('bar'));
        $this->assertSame(null, $session->oldInput('boo'));
        
        $this->assertSame(null, $session->oldInput('boo', 'default'));
        $this->assertSame('default', $session->oldInput('bogus', 'default'));
    }
    
    /** @test */
    public function testReplace()
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->replace(['foo' => 'baz']);
        $this->assertSame('baz', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
    }
    
    /** @test */
    public function testPutIfMissing()
    {
        $session = $this->newSession();
        
        $session->putIfMissing('foo', function () {
            return 'bar';
        });
        $this->assertSame('bar', $session->get('foo'));
        
        $session->put('baz', 'biz');
        
        $session->putIfMissing('baz', function () {
            $this->fail('This should not have been called');
        });
        
        $this->assertSame('biz', $session->get('baz'));
    }
    
    /** @test */
    public function testPush()
    {
        $session = $this->newSession();
        
        $session->put('foo', ['bar']);
        $session->push('foo', 'bar');
        $session->push('foo', ['baz' => 'biz']);
        
        $this->assertSame(['bar', 'bar', ['baz' => 'biz']], $session->get('foo'));
    }
    
    /** @test */
    public function testIncrement()
    {
        $session = $this->newSession();
        
        $session->put('foo', 5);
        $session->increment('foo');
        $this->assertEquals(6, $session->get('foo'));
        
        $session->increment('foo', 4);
        $this->assertEquals(10, $session->get('foo'));
        
        $this->assertEquals(0, $session->get('bar'));
        $session->increment('bar');
        $this->assertEquals(1, $session->get('bar'));
    }
    
    /** @test */
    public function testDecrement()
    {
        $session = $this->newSession();
        
        $session->put('foo', 5);
        $session->decrement('foo');
        $this->assertEquals(4, $session->get('foo'));
        
        $session->decrement('foo', 4);
        $this->assertEquals(0, $session->get('foo'));
        
        $this->assertEquals(0, $session->get('bar'));
        $session->decrement('bar');
        $this->assertEquals(-1, $session->get('bar'));
    }
    
    /** @test */
    public function testFlash()
    {
        $session = $this->newSession();
        $session->flash('foo', 'bar');
        $session->flash('bar', 0);
        $session->flash('baz');
        
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame(0, $session->get('bar'));
        $this->assertSame(true, $session->get('baz'));
        
        $session->saveUsing($driver = new ArraySessionDriver(), new DateTimeImmutable());
        
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));
        
        $session = $this->reloadSession($session, $driver);
        $session->saveUsing($driver, new DateTimeImmutable());
        
        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }
    
    /** @test */
    public function testflashNow()
    {
        $session = $this->newSession();
        $session->flashNow('foo', 'bar');
        $session->flashNow('bar', 0);
    
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));
    
        $session->saveUsing(new ArraySessionDriver(), new DateTimeImmutable());
        
        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }
    
    /** @test */
    public function testReflash()
    {
        $session = $this->newSession();
        $session->flash('foo', 'bar');
        $session->put('_flash.old', ['foo']);
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }
    
    /** @test */
    public function testReflashWithNow()
    {
        $session = $this->newSession();
        $session->flashNow('foo', 'bar');
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }
    
    /** @test */
    public function testFlashInput()
    {
        $session = $this->newSession();
        $session->put('boom', 'baz');
        $session->flashInput(['foo' => 'bar', 'bar' => 0]);
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertEquals(0, $session->oldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));
        
        $session->saveUsing(new ArraySessionDriver(), new DateTimeImmutable());
        
        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertEquals(0, $session->oldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));
    }
    
    /** @test */
    public function testKeep()
    {
        $session = $this->newSession();
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
    public function testRemove()
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        
        $session->remove('foo');
        
        $this->assertSame('biz', $session->get('baz'));
        $this->assertFalse($session->has('foo'));
    }
    
    /** @test */
    public function testForget()
    {
        $session = $this->newSession();
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
    public function testFlush()
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->put('boo', ['boom', 'bang' => 'bam']);
        
        $session->flush();
        
        $this->assertSame([], $session->all());
    }
    
    /** @test */
    public function session_errors_can_be_set()
    {
        $session = $this->newSession();
        
        $session->withErrors(['foo' => 'bar']);
        
        $errors = $session->errors();
        $this->assertSame('bar', $errors->first('foo'));
        
        $session->saveUsing($driver = new ArraySessionDriver(), new DateTimeImmutable());
        
        $errors = $session->errors();
        $this->assertSame('bar', $errors->first('foo'));
        
        $session = $this->reloadSession($session, $driver);
        $session->saveUsing($driver, new DateTimeImmutable());
        
        $this->assertSame('', $session->errors()->first('foo'));
    }
    
    /** @test */
    public function retrieved_errors_are_immutable()
    {
        $session = $this->newSession();
        $session->withErrors(['foo' => 'bar']);
        
        $errors1 = $session->errors();
        $this->assertTrue($errors1->has('foo'));
        
        $errors1->add('baz', 'biz');
        
        $errors2 = $session->errors();
        $this->assertFalse($errors2->has('baz'));
    }
    
    /** @test */
    public function testBoolean()
    {
        $session = $this->newSession();
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
    public function testTimestampValuesStayTheSame()
    {
        $id = SessionId::createFresh();
        
        $session = new Session($id, [], new DateTimeImmutable());
        
        $created_at = $session->createdAt();
        $last_rotated = $session->lastRotation();
        $last_activity = $session->lastActivity();
        
        $this->assertSame(time(), $created_at);
        $this->assertSame(time(), $last_rotated);
        $this->assertSame(time(), $last_activity);
        
        $driver = new ArraySessionDriver();
        $session->saveUsing($driver, new DateTimeImmutable());
        
        sleep(1);
        
        $session = $this->reloadSession($session, $driver);
        
        $this->assertSame($created_at, $session->createdAt());
        $this->assertSame($last_rotated, $session->lastActivity());
        $this->assertSame($last_activity, $session->lastRotation());
    }
    
    /** @test */
    public function testLastActivityIsUpdatedOnSave()
    {
        $id = SessionId::createFresh();
        
        $session = new Session($id, [], new DateTimeImmutable());
        
        $last_activity = $session->lastActivity();
        
        $this->assertSame(time(), $last_activity);
        
        sleep(1);
        
        $driver = new ArraySessionDriver();
        $session->saveUsing($driver, new DateTimeImmutable());
        
        $session = $this->reloadSession($session, $driver);
        
        $this->assertSame($last_activity + 1, $session->lastActivity());
    }
    
    /** @test */
    public function testIsDirtyAfterAttributeChange()
    {
        $session = $this->newPersistetSession();
        
        $this->assertFalse($session->isDirty());
        
        $session->put('foo', 'bar');
        
        $this->assertTrue($session->isDirty());
    }
    
    /** @test */
    public function testIsDirtyAfterRotating()
    {
        $session = $this->newPersistetSession();
        $this->assertFalse($session->isDirty());
        
        $session->rotate();
        
        $this->assertTrue($session->isDirty());
    }
    
    /** @test */
    public function testIsDirtyAfterInvalidating()
    {
        $session = $this->newPersistetSession();
        $this->assertFalse($session->isDirty());
        
        $session->invalidate();
        
        $this->assertTrue($session->isDirty());
    }
    
    /** @test */
    public function testIsDirtyWithFlashData()
    {
        $old_session = $this->newPersistetSession();
        $old_session->flash('foo', 'bar');
        
        $this->assertTrue($old_session->isDirty());
        
        $driver = new ArraySessionDriver();
        
        $old_session->saveUsing($driver, new DateTimeImmutable());
        
        $new_session = $this->reloadSession($old_session, $driver);
        
        $this->assertTrue($new_session->isDirty());
        
        $new_session->saveUsing($driver, new DateTimeImmutable());
        
        $data = $driver->read($old_session->id()->asHash());
        $new_session = new Session($new_session->id(), $data->asArray(), $data->lastActivity());
        
        $this->assertFalse($new_session->isDirty());
    }
    
    /** @test */
    public function testIsAlwaysDirtyAfterCreating()
    {
        $driver = new ArraySessionDriver();
        $session = $this->newSession();
        $this->assertTrue($session->isDirty());
        
        $session->saveUsing($driver, new DateTimeImmutable());
        
        $session = $this->reloadSession($session, $driver);
        $this->assertFalse($session->isDirty());
    }
    
    /** @test */
    public function a_dirty_session_is_saved_to_the_driver()
    {
        $spy_driver = Mockery::spy(SessionDriver::class);
        $session = $this->newPersistetSession();
        
        $session->put('foo', 'bar');
        
        $session->saveUsing($spy_driver, new DateTimeImmutable());
        
        $spy_driver->shouldHaveReceived('write')->once();
        $spy_driver->shouldNotHaveReceived('touch');
        
        Mockery::close();
    }
    
    /** @test */
    public function a_clean_session_is_saved_to_the_driver()
    {
        $spy_driver = Mockery::spy(SessionDriver::class);
        $session = $this->newPersistetSession();
        
        $session->saveUsing($spy_driver, new DateTimeImmutable());
        
        $spy_driver->shouldHaveReceived('touch')->once();
        $spy_driver->shouldNotHaveReceived('write');
        
        Mockery::close();
    }
    
    /** @test */
    public function the_session_id_is_changed_immediately_after_rotating()
    {
        $session = $this->newSession();
        $old_id = $session->id();
        
        $session->rotate();
        
        $new_id = $session->id();
        $this->assertFalse($old_id->sameAs($new_id));
    }
    
    /** @test */
    public function the_session_id_is_changed_immediately_after_invalidating()
    {
        $session = $this->newSession();
        $old_id = $session->id();
        
        $session->invalidate();
        
        $new_id = $session->id();
        $this->assertFalse($old_id->sameAs($new_id));
    }
    
    /** @test */
    public function the_csrf_token_is_present_after_construction()
    {
        $driver = new ArraySessionDriver();
        $session = $this->newPersistetSession(null, [], $driver);
        
        $this->assertNotEmpty($token1 = $session->csrfToken());
        
        $session_new = $this->newSession($session->id()->asString());
        $this->assertNotEmpty($token2 = $session_new->csrfToken());
        $this->assertSame($token1->asString(), $token2->asString());
        $this->assertNotSame($token1->asString(), $session->id()->asString());
        $this->assertNotSame($token1->asString(), $session->id()->asHash());
    }
    
    /** @test */
    public function the_csrf_token_is_rotated_after_rotating_the_session_id()
    {
        $session = $this->newSession();
        $token_old = $session->csrfToken();
        
        $session->rotate();
        $token_new = $session->csrfToken();
        
        $this->assertNotSame($token_new, $token_old);
    }
    
    /** @test */
    public function testSessionRotatedEventIsStored()
    {
        $session = $this->newSession();
        $this->assertEmpty($session->releaseEvents());
        
        $session->rotate();
        
        $events = $session->releaseEvents();
        $this->assertNotEmpty($events);
        $this->assertCount(1, $events);
        $this->assertInstanceOf(SessionRotated::class, $events[0]);
        
        $this->assertEmpty($session->releaseEvents());
    }
    
    private function newSession(string $id = null, array $data = []) :Session
    {
        $id = $id ? SessionId::fromCookieId($id) : SessionId::createFresh();
        
        return new Session($id, $data, new DateTimeImmutable());
    }
    
    private function reloadSession(Session $session, SessionDriver $driver) :Session
    {
        $data = $driver->read($session->id()->asHash());
        return new Session($session->id(), $data->asArray(), $data->lastActivity());
    }
    
    private function newPersistetSession(string $id = null, array $data = [], $driver = null) :Session
    {
        $session = $this->newSession($id, $data);
        $driver = $driver ?? new ArraySessionDriver();
        $session->saveUsing($driver, new DateTimeImmutable());
        return $this->reloadSession($session, $driver);
    }
    
}


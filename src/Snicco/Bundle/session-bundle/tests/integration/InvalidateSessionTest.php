<?php

declare(strict_types=1);

namespace Tests\SessionBundle\integration;

use Snicco\Component\StrArr\Str;
use Snicco\Component\HttpRouting\Http\Cookies;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\SessionBundle\SessionServiceProvider;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\HttpRouting\Http\ResponseEmitter;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

use function do_action;

class InvalidateSessionTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->cookie_pre = $_COOKIE;
        $this->afterApplicationCreated(function () {
            $this->withRequest($this->frontendRequest('POST', '/wp-login.php'));
            $this->bootApp();
        });
        parent::setUp();
    }
    
    protected function tearDown() :void
    {
        $_COOKIE = $this->cookie_pre;
        parent::tearDown();
    }
    
    /** @test */
    public function the_session_id_is_regenerated_on_a_login_event()
    {
        /** @var SessionConfig $session_config */
        $session_config = $this->app->resolve(SessionConfig::class);
        $session_id = SessionId::createFresh();
        
        $_COOKIE[$session_config->cookieName()] = $session_id->asString();
        
        /** @var InMemoryDriver $array_driver */
        $array_driver = $this->sessionDriver();
        
        $array_driver->write(
            $session_id->asHash(),
            SerializedSessionData::fromArray(
                ['foo' => 'bar'],
                time()
            )
        );
        
        $this->assertCount(1, $array_driver->all());
        
        $calvin = $this->createAdmin();
        do_action('wp_login', $calvin->user_login, $calvin);
        
        // No response got sent
        $this->assertNoResponse();
        
        // The old session id is missing in the driver
        try {
            $this->sessionDriver()->read($session_id->asHash());
            $this->fail('session id not rotated');
        } catch (BadSessionID $id) {
            //
        }
        
        // The session has a new id.
        $this->assertCount(1, $array_driver->all());
        
        $new_id_as_hash = array_key_first($array_driver->all());
        
        $this->assertNotSame($new_id_as_hash, $session_id->asHash());
        
        // The new session cookie got send
        $cookies = $this->sentCookies()->toHeaders();
        $this->assertStringContainsString("{$session_config->cookieName()}=", $cookies[0]);
        
        $sent_id_as_string = explode('=', Str::beforeFirst($cookies[0], ';'))[1];
        $this->assertSame($new_id_as_hash, SessionId::fromCookieId($sent_id_as_string)->asHash());
        
        // The data is the same
        $data = $array_driver->read($new_id_as_hash);
        $this->assertSame('bar', $data->asArray()['foo']);
    }
    
    /** @test */
    public function session_are_invalidated_on_logout()
    {
        /** @var SessionConfig $session_config */
        $session_config = $this->app->resolve(SessionConfig::class);
        $session_id = SessionId::createFresh();
        
        $_COOKIE[$session_config->cookieName()] = $session_id->asString();
        
        /** @var InMemoryDriver $array_driver */
        $array_driver = $this->sessionDriver();
        
        $array_driver->write(
            $session_id->asHash(),
            SerializedSessionData::fromArray(
                ['foo' => 'bar'],
                time()
            )
        );
        
        $this->assertCount(1, $array_driver->all());
        
        $calvin = $this->createAdmin();
        do_action('wp_logout', $calvin->ID);
        
        // No response got sent
        $this->assertNoResponse();
        
        // The old session id is missing in the driver
        try {
            $this->sessionDriver()->read($session_id->asHash());
            $this->fail('session id not rotated');
        } catch (BadSessionID $id) {
            //
        }
        
        // The session has a new id.
        $this->assertCount(1, $array_driver->all());
        
        $new_id_as_hash = array_key_first($array_driver->all());
        
        $this->assertNotSame($new_id_as_hash, $session_id->asHash());
        
        // The new session cookie got send
        $cookies = $this->sentCookies()->toHeaders();
        $this->assertStringContainsString("{$session_config->cookieName()}=", $cookies[0]);
        
        $sent_id_as_string = explode('=', Str::beforeFirst($cookies[0], ';'))[1];
        $this->assertSame($new_id_as_hash, SessionId::fromCookieId($sent_id_as_string)->asHash());
        
        // The data is gone
        $data = $array_driver->read($new_id_as_hash);
        $this->assertArrayNotHasKey('foo', $data->asArray());
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
        ];
    }
    
    private function sentCookies() :Cookies
    {
        $emitter = $this->app->resolve(ResponseEmitter::class);
        return $emitter->cookies;
    }
    
}
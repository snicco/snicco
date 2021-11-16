<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Session\Session;
use Snicco\Application\Application;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Middleware\VerifyCsrfToken;

/**
 * @property Session|null $session
 * @property Application $app
 * @property array $default_headers
 * @property array $default_cookies
 */
trait InteractsWithSession
{
    
    protected array $internal_keys = [
        '_user',
        '_url.previous',
        '_rotate_at',
        '_expires_at',
        '_last_activity',
        VerifyCsrfToken::TOKEN_KEY,
    ];
    
    private ?string $session_id = null;
    
    private bool $data_saved_to_driver = false;
    
    protected function withCsrfToken() :array
    {
        $this->withDataInSession($data = [VerifyCsrfToken::TOKEN_KEY => Str::random(40)]);
        
        return $data;
    }
    
    /**
     * @param  array  $data  Keys are expected to be in dot notation
     */
    protected function withDataInSession(array $data, string $id = null) :self
    {
        foreach ($data as $key => $value) {
            Arr::set($to_driver, $key, $value);
            $this->session->put($key, $value);
        }
        
        if ( ! $this->session_id) {
            $id = $id ?? $this->testSessionId();
            $write_to = $this->hash($id);
            
            // We need to safe at least the session id in the driver so that it does
            // not get invalidated since the framework does not accept session ids
            // that are not in the current driver
            $this->sessionDriver()->write($write_to, serialize([]));
            $this->session->setId($id);
            $this->session_id = $id;
            $this->withSessionCookie();
        }
        
        return $this;
    }
    
    protected function testSessionId() :string
    {
        return $this->session_id ?? str_repeat('a', 64);
    }
    
    protected function hash($id)
    {
        return hash('sha256', $id);
    }
    
    protected function sessionDriver() :SessionDriver
    {
        return $this->app->resolve(SessionDriver::class);
    }
    
    protected function withSessionCookie(string $name = 'snicco_test_session') :self
    {
        $this->default_cookies[$name] = $this->testSessionId();
        return $this;
    }
    
    protected function withSessionId(string $id) :self
    {
        $this->session_id = $id;
        
        return $this;
    }
    
    protected function assertDriverHas($expected, string $key, $id = null)
    {
        $data =
            unserialize($this->sessionDriver()->read($this->hash($id ?? $this->testSessionId())));
        
        PHPUnit::assertSame(
            $expected,
            Arr::get($data, $key, 'null'),
            "The session driver does not have the correct value for [$key]"
        );
    }
    
    protected function assertDriverNotHas(string $key, $id = null)
    {
        $data =
            unserialize($this->sessionDriver()->read($this->hash($id ?? $this->testSessionId())));
        
        PHPUnit::assertNull(
            Arr::get($data, $key),
            "Unexpect value in the session driver for [$key]"
        );
    }
    
    protected function assertDriverEmpty(string $id)
    {
        $data = $this->sessionDriver()->read($this->hash($id));
        
        if ($data === '') {
            return;
        }
        
        $data = unserialize($data);
        Arr::forget($data, $this->internal_keys);
        
        PHPUnit::assertEmpty($data['_flash']['old'], "The flash key is not empty for id [$id].");
        PHPUnit::assertEmpty($data['_flash']['new'], "The flash key is not empty for id [$id].");
        PHPUnit::assertEmpty($data['_url'], "The session driver is not empty for id [$id].");
        
        Arr::forget($data, '_flash');
        Arr::forget($data, '_url');
        
        $keys = implode(',', array_keys($data));
        PHPUnit::assertEmpty(
            $data,
            "The session driver is not empty for id [$id].".PHP_EOL."Found keys [$keys]"
        );
    }
    
    protected function assertSessionUserId(int $id)
    {
        PHPUnit::assertSame($id, $this->session->userId());
    }
    
}
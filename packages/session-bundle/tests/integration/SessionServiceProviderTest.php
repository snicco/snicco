<?php

declare(strict_types=1);

namespace Tests\SessionBundle\integration;

use LogicException;
use Snicco\ViewBundle\ViewServiceProvider;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\EncryptedDriver;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\Contracts\SessionEncryptor;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Session\Drivers\WPObjectCacheDriver;
use Snicco\SessionBundle\SessionServiceProvider;
use Snicco\Session\Drivers\DatabaseSessionDriver;
use Snicco\SessionBundle\Middleware\StartSession;
use Snicco\SessionBundle\Middleware\VerifyCsrfToken;
use Snicco\Session\Contracts\SessionEventDispatcher;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\SessionBundle\Middleware\AllowMutableSession;
use Snicco\SessionBundle\Middleware\HandleStatefulRequest;
use Snicco\SessionBundle\Middleware\ShareSessionWithViews;
use Snicco\DefuseEncryption\DefuseEncryptionServiceProvider;
use Snicco\SessionBundle\Middleware\AddResponseAttributesToSession;
use Snicco\SessionBundle\BetterWPHooks\SessionEventDispatcherUsingBetterWPHooks;

class SessionServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_session_config_is_bound()
    {
        $this->withAddedConfig(['session.path' => 'foobar'])->bootApp();
        
        /** @var SessionConfig $config1 */
        $config1 = $this->app[SessionConfig::class];
        $this->assertInstanceOf(SessionConfig::class, $config1);
        
        $config2 = $this->app[SessionConfig::class];
        
        $this->assertSame($config1, $config2);
        $this->assertSame('/foobar', $config1->cookiePath());
    }
    
    /** @test */
    public function the_session_table_has_a_default_value()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame('sessions', $this->app->config('session.table'));
    }
    
    /** @test */
    public function session_encryption_is_turned_of_by_default()
    {
        $this->bootApp();
        
        $this->assertFalse($this->app->config('session.encrypt'));
    }
    
    /** @test */
    public function the_database_driver_can_be_used()
    {
        $this->withAddedConfig(['session.driver' => 'database'])->bootApp();
        
        $driver = $this->app->resolve(SessionDriver::class);
        
        $this->assertInstanceOf(DatabaseSessionDriver::class, $driver);
    }
    
    /** @test */
    public function the_object_cache_driver_can_be_used()
    {
        $this->withAddedConfig(
            [
                'session.driver' => 'wp_object_cache',
                'session.wp_object_cache_group' => 'my_group',
            ]
        )->bootApp();
        
        $driver = $this->app->resolve(SessionDriver::class);
        
        $this->assertInstanceOf(WPObjectCacheDriver::class, $driver);
    }
    
    /** @test */
    public function test_encrypted_driver_exception_without_session_encryptor_bound()
    {
        $this->withAddedConfig(
            [
                'session.driver' => 'wp_object_cache',
                'session.encrypt' => true,
                'session.wp_object_cache_group' => 'my_group',
            ]
        )->bootApp();
        
        $this->expectException(LogicException::class);
        
        $driver = $this->app->resolve(SessionDriver::class);
    }
    
    /** @test */
    public function test_with_encrypted_driver_with_session_encryptor()
    {
        $this->withAddedConfig(
            [
                'session.driver' => 'wp_object_cache',
                'session.encrypt' => true,
                'session.wp_object_cache_group' => 'my_group',
            ]
        );
        $this->app->container()->instance(
            SessionEncryptor::class,
            new class implements SessionEncryptor
            {
                
                public function encrypt(string $data) :string
                {
                    return $data;
                }
                
                public function decrypt(string $data) :string
                {
                    return $data;
                }
                
            }
        );
        
        $this->bootApp();
        
        $driver = $this->app->resolve(SessionDriver::class);
        $this->assertInstanceOf(EncryptedDriver::class, $driver);
    }
    
    /** @test */
    public function middleware_is_configured()
    {
        $this->bootApp();
        
        $aliases = $this->app->config('middleware.aliases');
        
        $this->assertSame(VerifyCsrfToken::class, $aliases['csrf']);
        $this->assertSame(AllowMutableSession::class, $aliases['session.write']);
        
        $priority = $this->app->config('middleware.priority');
        $this->assertContains(StartSession::class, $priority);
        $this->assertContains(AllowMutableSession::class, $priority);
        $this->assertContains(VerifyCsrfToken::class, $priority);
        $this->assertContains(HandleStatefulRequest::class, $priority);
        $this->assertContains(ShareSessionWithViews::class, $priority);
        $this->assertNotContains(AddResponseAttributesToSession::class, $priority);
        
        $key1 = array_search(StartSession::class, $priority);
        $key2 = array_search(AllowMutableSession::class, $priority);
        $key3 = array_search(VerifyCsrfToken::class, $priority);
        $key4 = array_search(HandleStatefulRequest::class, $priority);
        $key5 = array_search(ShareSessionWithViews::class, $priority);
        
        $this->assertTrue($key1 < $key2 && $key2 < $key3 && $key3 < $key4 && $key4 < $key5);
        
        $groups = $this->app->config('middleware.groups');
        $this->assertArrayHasKey('stateful', $groups);
        $this->assertSame([
            StartSession::class,
            VerifyCsrfToken::class,
            HandleStatefulRequest::class,
            ShareSessionWithViews::class,
            AddResponseAttributesToSession::class,
        ], $groups['stateful']);
    }
    
    /** @test */
    public function all_middleware_can_be_resolved()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(VerifyCsrfToken::class, $this->app[VerifyCsrfToken::class]);
        $this->assertInstanceOf(AllowMutableSession::class, $this->app[AllowMutableSession::class]);
        $this->assertInstanceOf(StartSession::class, $this->app[StartSession::class]);
        $this->assertInstanceOf(AllowMutableSession::class, $this->app[AllowMutableSession::class]);
        $this->assertInstanceOf(
            HandleStatefulRequest::class,
            $this->app[HandleStatefulRequest::class]
        );
        $this->assertInstanceOf(
            ShareSessionWithViews::class,
            $this->app[ShareSessionWithViews::class]
        );
        $this->assertInstanceOf(
            AddResponseAttributesToSession::class,
            $this->app[AddResponseAttributesToSession::class]
        );
    }
    
    /** @test */
    public function the_session_manager_is_bound()
    {
        $this->bootApp();
        
        $manager = $this->app[SessionManagerInterface::class];
        $manager2 = $this->app[SessionManagerInterface::class];
        
        $this->assertInstanceOf(SessionManagerInterface::class, $manager);
        $this->assertSame($manager, $manager2);
    }
    
    /** @test */
    public function the_session_event_dispatcher_is_bound()
    {
        $this->bootApp();
        
        $dispatcher = $this->app[SessionEventDispatcher::class];
        
        $this->assertInstanceOf(SessionEventDispatcherUsingBetterWPHooks::class, $dispatcher);
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
            DefuseEncryptionServiceProvider::class,
            ViewServiceProvider::class,
        ];
    }
    
}

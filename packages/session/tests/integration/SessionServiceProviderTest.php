<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Snicco\Session\Session;
use Snicco\Session\CsrfField;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\Redirector;
use Snicco\View\GlobalViewContext;
use Snicco\Session\SessionManager;
use Snicco\Http\StatelessRedirector;
use Snicco\Session\EncryptedSession;
use Snicco\Session\StatefulRedirector;
use Snicco\Session\SessionServiceProvider;
use Snicco\Session\Contracts\SessionDriver;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Session\Drivers\DatabaseSessionDriver;
use Snicco\Session\Middleware\StartSessionMiddleware;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\DefuseEncryption\DefuseEncryptionServiceProvider;

class SessionServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function sessions_are_disabled_by_default_if_not_provided_in_the_config()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $this->pushProvider($customize);
        
        $this->bootApp();
        
        $this->assertFalse(TestApp::config('session.enabled'));
    }
    
    /** @test */
    public function sessions_can_be_enabled_in_the_config()
    {
        $this->bootApp();
        $this->assertTrue(TestApp::config('session.enabled'));
    }
    
    /** @test */
    public function nothing_is_bound_if_session_are_not_enabled()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $this->pushProvider($customize)->bootApp();
        
        $global = TestApp::config('middleware.groups.global');
        
        $this->assertNotContains(StartSessionMiddleware::class, $global);
    }
    
    /** @test */
    public function the_cookie_name_has_a_default_value()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame('snicco_test_session', TestApp::config('session.cookie'));
    }
    
    /** @test */
    public function a_cookie_name_can_be_set()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
            'session.cookie' => 'test_cookie',
        ])->bootApp();
        
        $this->assertSame('test_cookie', TestApp::config('session.cookie'));
    }
    
    /** @test */
    public function the_session_table_has_a_default_value()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame('sessions', TestApp::config('session.table'));
    }
    
    /** @test */
    public function the_default_absolute_timeout_is_eight_hours()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $customize->add('session.enabled', true);
        $this->pushProvider($customize)->bootApp();
        
        $this->assertSame(28800, TestApp::config('session.lifetime'));
    }
    
    /** @test */
    public function the_rotation_timeout_is_half_of_the_absolute_timeout_by_default()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $customize->add('session.enabled', true);
        $this->pushProvider($customize)->bootApp();
        
        $this->assertSame(14400, TestApp::config('session.rotate'));
    }
    
    /** @test */
    public function the_default_lottery_chance_is_2_percent()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame([2, 100], TestApp::config('session.lottery'));
    }
    
    /** @test */
    public function the_session_cookie_path_is_root_by_default()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame('/', TestApp::config('session.path'));
    }
    
    /** @test */
    public function the_session_cookie_domain_is_null_by_default()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertNull(TestApp::config('session.domain', ''));
    }
    
    /** @test */
    public function the_session_cookie_is_set_to_only_secure()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertTrue(TestApp::config('session.secure'));
    }
    
    /** @test */
    public function the_session_cookie_is_set_to_http_only()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertTrue(TestApp::config('session.http_only'));
    }
    
    /** @test */
    public function same_site_is_set_to_lax()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertSame('lax', TestApp::config('session.same_site'));
    }
    
    /** @test */
    public function session_lifetime_is_set()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $customize->add('session.enabled', true);
        $this->pushProvider($customize)->bootApp();
        
        $this->assertSame(SessionManager::HOUR_IN_SEC * 8, TestApp::config('session.lifetime'));
    }
    
    /** @test */
    public function the_session_store_can_be_resolved()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $store = TestApp::resolve(Session::class);
        
        $this->assertInstanceOf(Session::class, $store);
    }
    
    /** @test */
    public function the_database_driver_is_used_by_default()
    {
        $customize = $this->customizeConfigProvider();
        $customize->remove('session');
        $customize->add('session.enabled', true);
        $this->pushProvider($customize)->bootApp();
        
        $driver = TestApp::resolve(SessionDriver::class);
        
        $this->assertInstanceOf(DatabaseSessionDriver::class, $driver);
    }
    
    /** @test */
    public function the_session_store_is_not_encrypted_by_default()
    {
        $this->withAddedConfig(['session.enabled' => true])->bootApp();
        
        $this->assertFalse(TestApp::config('session.encrypt', ''));
    }
    
    /** @test */
    public function the_session_store_can_be_encrypted()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
            'session.encrypt' => true,
        ])->bootApp();
        
        $driver = TestApp::resolve(Session::class);
        
        $this->assertInstanceOf(EncryptedSession::class, $driver);
    }
    
    /** @test */
    public function the_session_middleware_is_added_if_enabled()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->assertContains(
            StartSessionMiddleware::class,
            TestApp::config('middleware.groups.global')
        );
    }
    
    /** @test */
    public function the_session_can_be_resolved_as_an_alias()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->assertInstanceOf(Session::class, TestApp::session());
    }
    
    /** @test */
    public function a_csrf_field_can_be_created_as_an_alias()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->session->start();
        
        $html = TestApp::csrfField();
        $this->assertStringContainsString('_token', $html);
        $this->assertStringContainsString($this->session->csrfToken(), $html);
        $this->assertStringStartsWith('<input', $html);
    }
    
    /** @test */
    public function a_csrf_token_can_be_generated_as_ajax_token()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->session->start();
        $token = TestApp::csrf()->asStringToken();
        
        $this->assertSame('_token='.$this->session->csrfToken(), $token);
    }
    
    /** @test */
    public function a_csrf_token_can_be_generated_as_meta_content()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->session->start();
        $token = TestApp::csrf()->asMeta();
        
        $this->assertViewContent(
            '<meta name="_token"content="'.$this->session->csrfToken().'">',
            $token
        );
    }
    
    /** @test */
    public function middleware_aliases_are_bound()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $middleware_aliases = TestApp::config('middleware.aliases');
        
        $this->assertArrayHasKey('csrf', $middleware_aliases);
    }
    
    /** @test */
    public function global_middleware_is_bound()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $global_middleware = TestApp::config('middleware.groups.global');
        
        $this->assertContains(StartSessionMiddleware::class, $global_middleware);
    }
    
    /** @test */
    public function the_session_manager_is_bound()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->assertInstanceOf(SessionManager::class, TestApp::resolve(SessionManager::class));
        $this->assertInstanceOf(
            SessionManager::class,
            TestApp::resolve(SessionManagerInterface::class)
        );
    }
    
    /** @test */
    public function the_csrf_field_is_bound_to_the_global_view_context()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $context = TestApp::resolve(GlobalViewContext::class)->get();
        
        $this->assertInstanceOf(CsrfField::class, $context['csrf']);
    }
    
    /** @test */
    public function if_sessions_are_enabled_a_stateful_redirector_is_used()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $this->assertInstanceOf(
            StatefulRedirector::class,
            TestApp::resolve(Redirector::class)
        );
    }
    
    /** @test */
    public function the_response_factory_uses_a_stateful_redirector_if_enabled()
    {
        $this->withAddedConfig([
            'session.enabled' => true,
        ])->bootApp();
        
        $response_factory = TestApp::resolve(ResponseFactory::class);
        
        $r = $response_factory->redirect();
        $this->assertInstanceOf(StatefulRedirector::class, $r);
    }
    
    /** @test */
    public function if_sessions_are_not_enabled_a_normal_redirector_is_used()
    {
        $this->withAddedConfig([
            'session.enabled' => false,
        ])->bootApp();
        
        $this->assertInstanceOf(StatelessRedirector::class, TestApp::resolve(Redirector::class));
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
            DefuseEncryptionServiceProvider::class,
        ];
    }
    
}

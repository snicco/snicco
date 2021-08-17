<?php

namespace Tests\integration\Fail2Ban;

use Tests\TestCase;
use Tests\stubs\TestApp;
use Snicco\Fail2Ban\Fail2Ban;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Bannable;
use Snicco\Fail2Ban\PHPSyslogger;
use Snicco\Auth\AuthServiceProvider;
use Snicco\Fail2Ban\Contracts\Syslogger;
use Snicco\Session\SessionServiceProvider;
use Snicco\Contracts\ErrorHandlerInterface;
use Snicco\Fail2Ban\Fail2BanServiceProvider;
use Snicco\ExceptionHandling\ProductionErrorHandler;
use Snicco\Auth\Exceptions\FailedAuthenticationException;

class Fail2BanServiceProviderTest extends TestCase
{
    
    protected bool $defer_boot = true;
    
    public function packageProviders() :array
    {
        return [
            AuthServiceProvider::class,
            Fail2BanServiceProvider::class,
            SessionServiceProvider::class,
        ];
        
    }
    
    /** @test */
    public function fail2ban_is_disabled_by_default()
    {
        
        $this->withOutConfig('auth.fail2ban.enabled')->boot();
        $this->assertNull(TestApp::config('auth.fail2ban.enabled'));
        
    }
    
    /** @test */
    public function the_daemon_is_set()
    {
        
        $this->boot();
        $this->assertSame('sniccowp', TestApp::config('auth.fail2ban.daemon'));
        
    }
    
    /** @test */
    public function the_default_facility_is_set_to_LOG_AUTH()
    {
        
        $this->boot();
        $this->assertSame(LOG_AUTH, TestApp::config('auth.fail2ban.facility'));
        
    }
    
    /** @test */
    public function the_default_flags_are_set()
    {
        
        $this->boot();
        $this->assertSame(LOG_NDELAY | LOG_PID, TestApp::config('auth.fail2ban.flags'));
        
    }
    
    /** @test */
    public function the_fail2ban_class_is_bound()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(Fail2Ban::class, TestApp::resolve(Fail2Ban::class));
        
    }
    
    /** @test */
    public function the_php_syslog_is_used_by_default()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(PHPSyslogger::class, TestApp::resolve(Syslogger::class));
        
    }
    
    /** @test */
    public function reportable_exceptions_are_reported_by_fail2ban()
    {
        
        $this->boot();
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        $this->loadRoutes();
        
        $handler = $this->resolveErrorHandler();
        $e = new FailedAuthenticationException('Failed authentication');
        
        $handler->transformToResponse($e, $this->request->withAttribute('ip_address', '127.0.0.1'));
        
        $logger->assertLogOpened();
        $logger->assertLogOpenedWithPrefix('sniccowp');
        $logger->assertLogOpenedWithFlags(LOG_NDELAY | LOG_PID);
        $logger->assertLogOpenedForFacility(LOG_AUTH);
    
        $logger->assertLogEntry(LOG_WARNING, 'Failed authentication from 127.0.0.1');
    }
    
    private function resolveErrorHandler() :ProductionErrorHandler
    {
        return $this->app->resolve(ErrorHandlerInterface::class);
    }
    
    /** @test */
    public function reportable_exception_messages_can_be_filtered()
    {
        
        add_filter(
            'sniccowp_fail2ban_message',
            function ($message, Bannable $bannable, Request $request) {
                
                return 'my_custom_message';
                
            },
            10,
            3
        );
        
        $this->boot();
        $this->swap(Syslogger::class, $logger = new TestSysLogger());
        $this->loadRoutes();
        
        $handler = $this->resolveErrorHandler();
        $e = new FailedAuthenticationException('Failed Authentication');
        
        $handler->transformToResponse($e, $this->request->withAttribute('ip_address', '127.0.0.1'));
        
        $logger->assertLogOpened();
        $logger->assertLogOpenedWithPrefix('sniccowp');
        $logger->assertLogOpenedWithFlags(LOG_NDELAY | LOG_PID);
        $logger->assertLogOpenedForFacility(LOG_AUTH);
        
        $logger->assertLogEntry(LOG_WARNING, 'my_custom_message from 127.0.0.1');
        
    }
    
    protected function setUp() :void
    {
        $this->afterLoadingConfig(function () {
            
            $this->withReplacedConfig('auth.fail2ban.enabled', true);
            
        });
        
        parent::setUp();
        
    }
    
}
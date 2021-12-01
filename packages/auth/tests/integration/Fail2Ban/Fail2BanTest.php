<?php

namespace Tests\Auth\integration\Fail2Ban;

use Snicco\Http\Psr7\Request;
use Snicco\Auth\Fail2Ban\Fail2Ban;
use Snicco\Auth\Fail2Ban\Bannable;
use Snicco\Auth\Fail2Ban\TestSysLogger;
use Tests\Auth\integration\AuthTestCase;

class Fail2BanTest extends AuthTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function testReportOpensLog()
    {
        $fail2ban = new Fail2Ban(
            $logger = new TestSysLogger(),
            ['daemon' => 'test', 'facility' => LOG_AUTH, 'flags' => LOG_PID]
        );
        $request = $this->frontendRequest()->withAttribute('ip_address', '127.0.0.0');
        $fail2ban->report(new TestBannable($request));
        
        $logger->assertLogOpenedWithPrefix('test');
        $logger->assertLogOpenedForFacility(LOG_AUTH);
        $logger->assertLogOpenedWithFlags(LOG_PID);
    }
    
    /** @test */
    public function testReportLogsTheCorrectEntry()
    {
        $fail2ban = new Fail2Ban(
            $logger = new TestSysLogger(),
            ['daemon' => 'test', 'facility' => LOG_AUTH, 'flags' => LOG_PID]
        );
        $request = $this->frontendRequest()->withAttribute('ip_address', '127.0.0.1');
        $fail2ban->report(new TestBannable($request));
        
        $logger->assertLogEntry(E_WARNING, 'test ban message from 127.0.0.1');
    }
    
    /** @test */
    public function test_log_message_is_filterable()
    {
        add_filter('sniccowp_fail2ban_message', function ($message, Bannable $event) {
            return 'filtered message';
        }, 10, 2);
        
        $fail2ban = new Fail2Ban(
            $logger = new TestSysLogger(),
            ['daemon' => 'test', 'facility' => LOG_AUTH, 'flags' => LOG_PID]
        );
        $request = $this->frontendRequest()->withAttribute('ip_address', '127.0.0.1');
        $fail2ban->report(new TestBannable($request));
        
        $logger->assertLogEntry(E_WARNING, 'filtered message from 127.0.0.1');
    }
    
}

class TestBannable implements Bannable
{
    
    private Request $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function priority() :int
    {
        return E_WARNING;
    }
    
    public function fail2BanMessage() :string
    {
        return 'test ban message';
    }
    
    public function request() :Request
    {
        return $this->request;
    }
    
}
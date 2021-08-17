<?php

namespace Snicco\Fail2Ban;

use RuntimeException;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Bannable;
use Snicco\Fail2Ban\Contracts\Syslogger;

class Fail2Ban
{
    
    private Syslogger $syslogger;
    private array     $config;
    
    public function __construct(Syslogger $syslogger, array $config)
    {
        $this->syslogger = $syslogger;
        $this->config = $config;
    }
    
    public function report(Bannable $bannable, Request $request)
    {
        $opened = $this->syslogger->open(
            $this->config['daemon'],
            $this->config['flags'],
            $this->config['facility']
        );
        
        if ( ! $opened) {
            throw new RuntimeException("Failed to open syslog");
        }
        
        $this->syslogger->log($bannable->priority(), $this->formatMessage($bannable, $request));
    }
    
    private function formatMessage(Bannable $bannable, Request $request)
    {
        $message = apply_filters(
            'sniccowp_fail2ban_message',
            $bannable->fail2BanMessage(),
            $bannable,
            $request
        );
        return "{$message} from {$request->getAttribute('ip_address')}";
        
    }
    
}
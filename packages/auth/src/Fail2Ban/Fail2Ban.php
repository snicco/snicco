<?php

namespace Snicco\Auth\Fail2Ban;

use RuntimeException;

use function apply_filters;

class Fail2Ban
{
    
    private Syslogger $syslogger;
    private array     $config;
    
    public function __construct(Syslogger $syslogger, array $config)
    {
        $this->syslogger = $syslogger;
        $this->config = $config;
    }
    
    public function report(Bannable $event)
    {
        $opened = $this->syslogger->open(
            $this->config['daemon'],
            $this->config['flags'],
            $this->config['facility']
        );
        
        if ( ! $opened) {
            throw new RuntimeException("Failed to open syslog");
        }
        
        $this->syslogger->log($event->priority(), $this->formatMessage($event));
    }
    
    private function formatMessage(Bannable $event)
    {
        $message = apply_filters(
            'sniccowp_fail2ban_message',
            $event->fail2BanMessage(),
            $event,
        );
        return "{$message} from {$event->request()->getAttribute('ip_address')}";
    }
    
}
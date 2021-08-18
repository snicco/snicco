<?php

namespace Snicco\Fail2Ban;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Bannable;
use Snicco\Contracts\ServiceProvider;
use Snicco\Fail2Ban\Contracts\Syslogger;
use Snicco\Contracts\ErrorHandlerInterface;
use Snicco\Auth\Events\FailedPasswordReset;
use Snicco\ExceptionHandling\ProductionErrorHandler;

class Fail2BanServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        if ( ! $this->config->get('auth.fail2ban.enabled')) {
            return;
        }
        
        $this->bindConfig();
        $this->bindFail2Ban();
        $this->bindSyslogger();
        $this->bindEvents();
        
    }
    
    private function bindConfig()
    {
        
        $this->config->extend('auth.fail2ban.daemon', 'sniccowp');
        $this->config->extend('auth.fail2ban.facility', LOG_AUTH);
        $this->config->extend('auth.fail2ban.flags', LOG_NDELAY | LOG_PID);
        
    }
    
    private function bindFail2Ban()
    {
        $this->container->singleton(Fail2Ban::class, function () {
            return new Fail2Ban(
                $this->container->make(Syslogger::class),
                $this->config->get('auth.fail2ban')
            );
        });
    }
    
    private function bindSyslogger()
    {
        $this->container->singleton(Syslogger::class, function () {
            return new PHPSyslogger();
        });
    }
    
    function bootstrap() :void
    {
        $this->bindReporter();
    }
    
    private function bindReporter()
    {
        $error_handler = $this->container->make(ErrorHandlerInterface::class);
        
        if ( ! $error_handler instanceof ProductionErrorHandler) {
            return;
        }
    
        $error_handler->reportable(function (Bannable $exception, Request $request) {
        
            $this->container->make(Fail2Ban::class)->report($exception, $request);
        
        });
    
    }
    
    private function bindEvents()
    {
        $this->config->extend('events.listeners', [
            FailedPasswordReset::class => [
                [Fail2Ban::class, 'report'],
            ],
        ]);
    }
    
}
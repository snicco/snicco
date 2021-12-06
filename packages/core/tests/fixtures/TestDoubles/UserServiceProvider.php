<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\TestDoubles;

use Snicco\Contracts\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->config->set('events', []);
        $this->container->primitive('foo', 'bar');
    }
    
    public function bootstrap() :void
    {
        $this->container->primitive('foo_bootstrapped', 'bar_bootstrapped');
    }
    
}
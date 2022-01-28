<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\fixtures\bundles;

use stdClass;
use RuntimeException;
use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Configuration\WritableConfig;

class Bundle2 implements Bundle
{
    
    private ?string $alias;
    
    public function __construct(string $alias = null)
    {
        $this->alias = $alias;
    }
    
    public function alias() :string
    {
        return $this->alias ?? 'bundle2';
    }
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        if ( ! $config->has('bundle1.configured')) {
            throw new RuntimeException("bundle1 should have been configured first.");
        }
        $config->set('bundle2.configured', true);
    }
    
    public function register(Application $app) :void
    {
        if ( ! isset($app['bundle1.registered'])) {
            throw new RuntimeException('bundle1 should have been registered first');
        }
        $app['bundle2.registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app['bundle2.booted'] = $std;
    }
    
    public function bootstrap(Application $app) :void
    {
        if ( ! isset($app['bundle1.booted'])) {
            throw new RuntimeException('bundle1 should have been booted first');
        }
        $app['bundle2.booted']->val = true;
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}
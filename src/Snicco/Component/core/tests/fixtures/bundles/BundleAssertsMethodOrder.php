<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\fixtures\bundles;

use stdClass;
use RuntimeException;
use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Configuration\WritableConfig;

final class BundleAssertsMethodOrder implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
        $config->set($this->alias().'.configured', true);
    }
    
    public function register(Application $app) :void
    {
        if ( ! $app->config()->get($this->alias().'.configured')) {
            throw new RuntimeException("register was called before configure.");
        }
        $app[$this->alias().'.registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app[$this->alias().'.bootstrapped'] = $std;
    }
    
    public function bootstrap(Application $app) :void
    {
        if ( ! $app->config()->get($this->alias().'.configured')) {
            throw new RuntimeException('bootstrap was called before configure.');
        }
        
        if ( ! isset($app[$this->alias().'.registered'])) {
            throw new RuntimeException('bootstrap was called before register.');
        }
        
        $app[$this->alias().'.bootstrapped']->val = true;
    }
    
    public function alias() :string
    {
        return 'bundle_that_asserts_order';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}
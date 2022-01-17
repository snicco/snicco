<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\fixtures\bundles;

use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Configuration\WritableConfig;

final class BundleProduction implements Bundle
{
    
    public function configure(WritableConfig $config, Application $app) :void
    {
    }
    
    public function register(Application $app) :void
    {
        //
    }
    
    public function bootstrap(Application $app) :void
    {
        //
    }
    
    public function alias() :string
    {
        return 'bundle_prod';
    }
    
    public function runsInEnvironments(Environment $env) :bool
    {
        return true;
    }
    
}
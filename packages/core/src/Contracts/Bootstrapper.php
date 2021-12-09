<?php

namespace Snicco\Core\Contracts;

use Snicco\Core\Application\Application;

interface Bootstrapper
{
    
    public function bootstrap(Application $app) :void;
    
}
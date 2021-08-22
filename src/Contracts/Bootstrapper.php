<?php

namespace Snicco\Contracts;

use Snicco\Application\Application;

interface Bootstrapper
{
    
    public function bootstrap(Application $app) :void;
    
}
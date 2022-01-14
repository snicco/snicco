<?php

namespace Snicco\Core\Contracts;

use Snicco\Core\Application\Application_OLD;

interface Bootstrapper
{
    
    public function bootstrap(Application_OLD $app) :void;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface RouteAction extends Handler
{
    
    public function raw();
    
}
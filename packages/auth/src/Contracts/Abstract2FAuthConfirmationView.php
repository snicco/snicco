<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Http\Psr7\Request;
use Snicco\View\Contracts\ViewInterface;

abstract class Abstract2FAuthConfirmationView
{
    
    abstract public function toView(Request $request) :ViewInterface;
    
}
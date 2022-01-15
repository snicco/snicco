<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\View\Contracts\ViewInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

abstract class Abstract2FAuthConfirmationView
{
    
    abstract public function toView(Request $request) :ViewInterface;
    
}
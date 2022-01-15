<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\View\Contracts\ViewInterface;
use Snicco\HttpRouting\Http\Psr7\Request;

abstract class AbstractEmailAuthConfirmationView
{
    
    abstract public function toView(Request $request) :ViewInterface;
    
}
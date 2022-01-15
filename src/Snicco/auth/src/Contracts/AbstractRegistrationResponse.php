<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Auth\Traits\UsesCurrentRequest;
use Snicco\Component\HttpRouting\Http\Responsable;

abstract class AbstractRegistrationResponse implements Responsable
{
    
    use UsesCurrentRequest;
}
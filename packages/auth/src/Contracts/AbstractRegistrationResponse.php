<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Core\Http\Responsable;
use Snicco\Auth\Traits\UsesCurrentRequest;

abstract class AbstractRegistrationResponse implements Responsable
{
    
    use UsesCurrentRequest;
}
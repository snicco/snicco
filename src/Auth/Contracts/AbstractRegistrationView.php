<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Auth\Traits\UsesCurrentRequest;
use Snicco\Contracts\Responsable;

abstract class AbstractRegistrationView implements Responsable
{
    
    use UsesCurrentRequest;
}
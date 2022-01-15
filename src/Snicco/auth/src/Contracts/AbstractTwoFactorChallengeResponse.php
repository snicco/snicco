<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\HttpRouting\Http\Responsable;
use Snicco\Auth\Traits\UsesCurrentRequest;

abstract class AbstractTwoFactorChallengeResponse implements Responsable
{
    
    use UsesCurrentRequest;
}
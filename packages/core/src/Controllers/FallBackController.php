<?php

declare(strict_types=1);

namespace Snicco\Core\Controllers;

use Snicco\Core\Http\AbstractController;
use Snicco\Core\Http\Responses\DelegatedResponse;

/**
 * @interal
 */
final class FallBackController extends AbstractController
{
    
    public function delegate() :DelegatedResponse
    {
        return $this->respond()->delegateToWP();
    }
    
}
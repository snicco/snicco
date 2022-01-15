<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\Controller;

use Snicco\HttpRouting\Http\AbstractController;
use Snicco\HttpRouting\Http\Responses\DelegatedResponse;

/**
 * @interal
 */
final class FallBackController extends AbstractController
{
    
    public function delegate() :DelegatedResponse
    {
        return $this->respond()->delegate(true);
    }
    
}
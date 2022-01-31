<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Controller;

use Snicco\Component\HttpRouting\AbstractController;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;

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
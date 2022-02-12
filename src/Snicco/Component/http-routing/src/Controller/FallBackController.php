<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;

/**
 * @interal
 */
final class FallBackController extends Controller
{

    public function delegate(): DelegatedResponse
    {
        return $this->respond()->delegate(true);
    }

}
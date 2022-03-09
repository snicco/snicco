<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Event;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

/**
 * @psalm-immutable
 */
final class HandlingRequest
{
    public Request $request;

    public float $time;

    public function __construct(Request $request, float $time)
    {
        $this->request = $request;
        $this->time = $time;
    }
}

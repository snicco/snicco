<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;

final class ControllerWithBarMiddleware extends Controller
{
    public function __construct()
    {
        $this->addMiddleware(BarMiddleware::class);
    }

    public function __invoke()
    {
        return 'controller';
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Controller\ControllerMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;

final class ControllerWithBarMiddleware extends Controller
{
    public function __invoke()
    {
        return 'controller';
    }

    public static function middleware()
    {
        yield new ControllerMiddleware(BarMiddleware::class);
    }
}

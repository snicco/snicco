<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Controller\ControllerMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Tests\fixtures\MiddlewareWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Baz;

final class ControllerWithMiddleware extends Controller
{
    /**
     * @var string
     */
    public const CONSTRUCTED_KEY = 'controller_with_middleware';

    private Baz $baz;

    public function __construct(Baz $baz)
    {
        $this->baz = $baz;

        $count = $GLOBALS['test'][self::CONSTRUCTED_KEY] ?? 0;
        ++$count;
        $GLOBALS['test'][self::CONSTRUCTED_KEY] = $count;
    }

    public static function middleware()
    {
        yield new ControllerMiddleware(MiddlewareWithDependencies::class);
    }

    public function handle(Request $request): string
    {
        return $this->baz->value . ':controller_with_middleware';
    }
}

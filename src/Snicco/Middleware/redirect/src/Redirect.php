<?php

declare(strict_types=1);

namespace Snicco\Middleware\Redirect;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

use function implode;
use function in_array;
use function ltrim;
use function sprintf;
use function strpos;

final class Redirect extends Middleware
{
    /**
     * @var array<string,array{to: string, status: positive-int}>
     */
    private array $redirects = [];

    /**
     * @param array<positive-int,array<string,string>> $redirects
     */
    public function __construct(array $redirects)
    {
        $this->redirects = $this->normalize($redirects);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if (isset($this->redirects[$request->path()])) {
            return $this->responseFactory()
                ->redirect($this->redirects[$request->path()]['to'], $this->redirects[$request->path()]['status']);
        }

        $path_qs = $request->path() . '?' . $request->queryString();

        if (isset($this->redirects[$path_qs])) {
            return $this->responseFactory()
                ->redirect($this->redirects[$path_qs]['to'], $this->redirects[$path_qs]['status']);
        }

        return $next($request);
    }

    /**
     * @param array<positive-int,array<string,string>> $redirects
     *
     * @return array<string,array{to: string, status: positive-int}>
     */
    private function normalize(array $redirects): array
    {
        $arr = [301, 302, 303, 307, 308];
        $_r = [];
        foreach ($redirects as $status => $redirect) {
            if (! in_array($status, $arr, true)) {
                throw new InvalidArgumentException(sprintf('$status must be one of [%s].', implode(',', $arr)));
            }

            foreach ($redirect as $from => $to) {
                $from = '/' . ltrim($from, '/');

                $to = (0 === strpos($to, 'http'))
                    ? $to
                    : '/' . ltrim($to, '/');

                $_r[$from] = [
                    'to' => $to,
                    'status' => $status,
                ];
            }
        }

        return $_r;
    }
}

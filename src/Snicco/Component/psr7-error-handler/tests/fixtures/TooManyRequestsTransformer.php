<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Throwable;

final class TooManyRequestsTransformer implements ExceptionTransformer
{
    public function transform(Throwable $e): Throwable
    {
        if ($e instanceof SlowDown) {
            return HttpException::fromPrevious(429, $e, [
                'X-Retry-After' => 10,
            ]);
        }

        return $e;
    }
}

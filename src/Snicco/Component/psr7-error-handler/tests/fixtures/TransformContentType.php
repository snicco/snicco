<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\fixtures;

use Throwable;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;

final class TransformContentType implements ExceptionTransformer
{
    
    public function transform(Throwable $e) :Throwable
    {
        return HttpException::fromPrevious(
            500,
            $e,
            ['content-type' => 'application/json', 'X-Foo' => 'BAR']
        );
    }
    
}
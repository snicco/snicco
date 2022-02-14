<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Exception;

use RuntimeException;
use Throwable;

final class CouldNotRenderTemplate extends RuntimeException
{
    public static function fromPrevious(Throwable $e): CouldNotRenderTemplate
    {
        return new self($e->getMessage(), (int)$e->getCode(), $e);
    }
}
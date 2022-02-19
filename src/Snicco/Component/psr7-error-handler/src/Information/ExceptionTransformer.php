<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Throwable;

interface ExceptionTransformer
{

    public function transform(Throwable $e): Throwable;

}
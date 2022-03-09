<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Log;

use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

interface RequestLogContext
{
    public function add(array $context, ExceptionInformation $information): array;
}

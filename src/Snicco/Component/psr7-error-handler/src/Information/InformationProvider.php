<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Snicco\Component\Psr7ErrorHandler\IdentifiedThrowable;

interface InformationProvider
{
    
    public function provideFor(IdentifiedThrowable $e) :ExceptionInformation;
    
}
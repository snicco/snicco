<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

interface TransportException
{
    
    public function getDebugData() :string;
    
}
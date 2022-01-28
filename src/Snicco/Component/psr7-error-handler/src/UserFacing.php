<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

interface UserFacing
{
    
    public function title() :string;
    
    public function safeDetails() :string;
    
}
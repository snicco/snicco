<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

interface HasCustomRoutePath
{
    
    public function toPath() :string;
    
}

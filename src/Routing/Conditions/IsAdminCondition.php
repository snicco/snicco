<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConditionInterface;

class IsAdminCondition implements ConditionInterface
{
    
    public function isSatisfied(Request $request) :bool
    {
        return WP::isAdmin();
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}
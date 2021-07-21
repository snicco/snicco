<?php


    declare(strict_types = 1);


    namespace Snicco\Routing\Conditions;

    use Snicco\Contracts\ConditionInterface;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;

    class IsAdminCondition implements ConditionInterface
    {

        public function isSatisfied(Request $request) : bool
        {
            return WP::isAdmin();
        }

        public function getArguments(Request $request) : array
        {
            return [];
        }

    }
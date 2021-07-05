<?php


    declare(strict_types = 1);


    namespace BetterWP\Routing\Conditions;

    use BetterWP\Contracts\ConditionInterface;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;

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
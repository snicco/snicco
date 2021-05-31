<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;

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
<?php


    declare(strict_types = 1);


    namespace WPMvc\Routing\Conditions;

    use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Request;

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
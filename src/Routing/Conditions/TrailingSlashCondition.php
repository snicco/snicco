<?php


    declare(strict_types = 1);


    namespace WPMvc\Routing\Conditions;

    use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Support\Str;

    /**
     * This Condition is required for make FastRoute only match trailing slash urls
     * if the last route segment is optional.
     */
    class TrailingSlashCondition implements ConditionInterface
    {

        public function isSatisfied(Request $request) : bool
        {
            $path = $request->path();

            $valid = Str::endsWith($path, '/');

            if ( $valid ) {
                return true;
            }

            $uses_trailing = WP::usesTrailingSlashes();

            if ( ! $uses_trailing ) {
                return true;
            }

            return false;

        }

        public function getArguments(Request $request) : array
        {
            return [];
        }

    }
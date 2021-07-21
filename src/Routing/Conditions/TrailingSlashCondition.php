<?php


    declare(strict_types = 1);


    namespace Snicco\Routing\Conditions;

    use Snicco\Contracts\ConditionInterface;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Str;

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
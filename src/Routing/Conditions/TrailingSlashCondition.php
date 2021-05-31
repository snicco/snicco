<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Support\Str;

    class TrailingSlashCondition implements ConditionInterface
    {


        public function isSatisfied(Request $request) : bool
        {
            $path = $request->getPath();

            return Str::endsWith($path, '/');

        }

        public function getArguments(Request $request) : array
        {
            return [];
        }

    }
<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Events\IncomingGlobalRequest;
    use WPEmerge\Http\Psr7\Request;

    class IsStandardRoute implements ConditionInterface {


        public function isSatisfied(Request $request) : bool
        {
            return $request->getType() !== IncomingGlobalRequest::class || $request->filtersWpQuery();
        }

        public function getArguments(Request $request) : array
        {
            return [];
        }

    }
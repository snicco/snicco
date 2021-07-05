<?php


    declare(strict_types = 1);


    namespace BetterWP\Routing\Conditions;

    use BetterWP\Contracts\UrlableInterface;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;

    class AdminPageCondition extends QueryStringCondition implements UrlableInterface
    {

        public function toUrl(array $arguments = []) : string
        {

            $page = $this->query_string_arguments['page'];

            return WP::pluginPageUrl($page);

        }

        public function isSatisfied(Request $request) :bool
        {

            return true;

        }

        public function getArguments(Request $request) : array
        {
            return [];
        }

    }
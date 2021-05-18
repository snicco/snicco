<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\Facade\WP;

    class AdminPageCondition extends QueryStringCondition implements UrlableInterface
    {

        public function toUrl(array $arguments = []) : string
        {

            $page = $this->query_string_arguments['page'];

            return WP::pluginPageUrl($page);

        }

    }
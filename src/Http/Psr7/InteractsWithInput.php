<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http\Psr7;

    use WPEmerge\Support\Arr;

    trait InteractsWithInput
    {


        public function getQueryString(string $key = null, $default = '') : string
        {

            $query_string = $this->getUri()->getQuery();

            if ( ! $key) {
                return $query_string;
            }

            parse_str($query_string, $query);

            return Arr::get($query, $key, $default);

        }

        public function getQuery(string $name = null, $default = null)
        {

            if ( ! $name) {

                return $this->getQueryParams() ?? [];

            }

            return Arr::get($this->getQueryParams(), $name, $default);

        }




    }
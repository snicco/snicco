<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Support\Str;
    use WPEmerge\Support\UrlParser;

    class RouteRegex
    {

        public function replaceOptional(string $url_pattern) : string
        {

            $optionals = UrlParser::replaceOptionalMatch($url_pattern);

            foreach ($optionals as $optional) {

                $optional = preg_quote($optional, '/');

                $pattern = sprintf("#(%s)#", $optional);

                $url_pattern = preg_replace_callback($pattern, function ($match) {

                    $cleaned_match = Str::between($match[0], '{', '?');

                    return sprintf("[/{%s}]", $cleaned_match);

                }, $url_pattern, 1);

            }

            while ($this->hasMultipleOptionalSegments(rtrim($url_pattern, '/'))) {

                $this->combineOptionalSegments($url_pattern);

            }

            return rtrim($url_pattern, '/');

        }

        public function hasMultipleOptionalSegments(string $url_pattern) : bool
        {

            $count = preg_match_all('/(?<=\[).*?(?=])/', $url_pattern, $matches);

            return $count > 1;

        }

        public function combineOptionalSegments(string &$url_pattern)
        {

            preg_match('/(\[(.*?)])/', $url_pattern, $matches);

            $first = $matches[0];

            $before = Str::before($url_pattern, $first);
            $after = Str::afterLast($url_pattern, $first);

            $url_pattern = $before.rtrim($first, ']').rtrim($after, '/').']';

        }

        public function parseUrlWithRegex( array $regex, string $url) : string
        {

            $segments = UrlParser::segments($url);

            $segments = array_filter($segments, function ($segment) use ($regex) {

                return isset($regex[$segment]);

            });

            foreach ($segments as $segment) {

                $pattern = sprintf("/(%s(?=\\}))/", preg_quote($segment, '/'));;

                $url = preg_replace_callback($pattern, function ($match) use ($regex) {

                    return $match[0].':'.$regex[$match[0]];

                }, $url, 1);

            }

            return rtrim($url, '/');

        }
    }
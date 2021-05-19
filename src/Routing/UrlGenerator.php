<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\Str;

    class UrlGenerator
    {

        private $relative       = false;
        private $trailing_slash = false;

        /**
         * @var RouteUrlGenerator
         */
        private $route_url;

        public function __construct(RouteUrlGenerator $route_url)
        {

            $this->route_url = $route_url;
        }

        public function toRoute(string $name, array $arguments = [], $absolute = true) : string
        {

            $path = $this->route_url->to($name, $arguments);

            return $this->formatPath($path, $absolute);

        }

        private function formatPath(string $path, bool $absolute = false, string $scheme = 'https') : string
        {

            if ($absolute) {

                $path = ($this->isAbsolute($path))
                    ? $path :
                    WP::homeUrl($path, $scheme);

                return $this->formatTrailing($path);

            }

            $path = '/'.trim($path, '/');

            return $this->formatTrailing($path);


        }


        private function formatTrailing(string $path) :string {

            return ($this->trailing_slash) ? $path.'/' : $path;

        }

        private function isAbsolute(string $url) : bool
        {

            return Str::contains($url, '://');

        }

        private function pathFromAbsolute(string $full_url) : string
        {

            return parse_url($full_url)['path'] ?? '/';

        }

    }
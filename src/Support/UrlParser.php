<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use WPEmerge\Facade\WP;

    use function collect;

    class UrlParser
    {

        public const default_key = 'id';

        public const ADMIN_ALIASES = [

            'admin' => 'admin.php',
            'options' => 'options-general.php',
            'tools' => 'tools.php',
            'users' => 'users.php',
            'plugins' => 'plugins.php',
            'themes' => 'themes.php',
            'comments' => 'edit-comments.php',
            'upload' => 'upload.php',
            'posts' => 'edit.php',
            'dashboard' => 'index.php',

        ];

        public static function parseModelsFromUrl(string $url_pattern) : array
        {

            preg_match_all('/[^{]+(?=})/', $url_pattern, $matches);

            $matches = collect($matches)->flatten();

            $model_blueprint = $matches->flatMap(function ($value) {

                $key = static::containsDot($value) ? Str::after($value, ':') : static::default_key;

                return [Str::before($value, ':') => $key];

            });

            return $model_blueprint->all();

        }

        public static function normalize(string $url) : string
        {

            while (Str::contains($url, ':')) {

                $before = Str::before($url, ':');

                $rest = Str::replaceFirst($before, '', $url);

                $column = Str::before($rest, '}');

                $url = $before.Str::replaceFirst($column, '', $rest);

            }

            return $url;

        }

        private static function containsDot($string) : bool
        {

            return Str::contains($string, ':');

        }

        public static function requiredSegments(string $url_pattern) : array
        {

            preg_match_all('/[^{]+\w(?=})/', $url_pattern, $matches);

            return collect($matches)->flatten()->all();

        }

        public static function segments(string $url_pattern) : array
        {

            preg_match_all('/[^{]+(?=})/', $url_pattern, $matches);

            return collect($matches)->flatten()->all();

        }

        public static function optionalSegments(string $url_pattern)
        {

            preg_match_all('/[^\/{]+[?]/', $url_pattern, $matches);

            return collect($matches)->flatten()->all();


        }

        public static function getOptionalSegments(string $url_pattern) : array
        {

            preg_match_all('/(\/{[^\/{]+[?]})/', $url_pattern, $matches);

            return collect($matches)->flatten()->unique()->all();

        }

        public static function getPageQueryVar(string $route_url) : string
        {

            $page = Str::after($route_url, '.php/');

            return $page;

        }

        public static function replaceAdminAliases(string $url) : string
        {

            $options = implode('|', array_keys(self::ADMIN_ALIASES));
            $admin = WP::wpAdminFolder();

            return preg_replace_callback(sprintf("/(?<=%s\\/)(%s)(?=\\/)/", $admin, $options), function ($matches) {

                return self::ADMIN_ALIASES[$matches[0]];

            }, $url);

        }

        public static function getAjaxAction(string $route_url) : string
        {

            return trim(Str::after($route_url, 'ajax.php/'), '/');

        }

        public static function segmentNames(string $url) : array
        {

            $segments = static::segments($url);

            return collect($segments)->map(function ($segment) {

                return trim($segment, '?');
            })->all();

        }


    }
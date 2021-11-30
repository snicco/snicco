<?php

declare(strict_types=1);

namespace Snicco\Support;

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
    
    public static function normalize(string $url) :string
    {
        while (Str::contains($url, ':')) {
            $before = Str::before($url, ':');
            
            $rest = Str::replaceFirst($before, '', $url);
            
            $column = Str::before($rest, '}');
            
            $url = $before.Str::replaceFirst($column, '', $rest);
        }
        
        return $url;
    }
    
    public static function requiredSegments(string $url_pattern) :array
    {
        preg_match_all('/[^{]+\w(?=})/', $url_pattern, $matches);
        
        return Arr::flattenOnePreserveKeys($matches);
    }
    
    public static function getOptionalSegments(string $url_pattern) :array
    {
        preg_match_all('/(\/{[^\/{]+[?]})/', $url_pattern, $matches);
        
        $matches = Arr::flatten($matches);
        return array_unique($matches);
    }
    
    public static function replaceAdminAliases(string $url) :string
    {
        $options = implode('|', array_keys(self::ADMIN_ALIASES));
        $admin = WP::wpAdminFolder();
        
        return preg_replace_callback(
            sprintf("/(?<=%s\\/)(%s)(?=\\/)/", $admin, $options),
            function ($matches) {
                return self::ADMIN_ALIASES[$matches[0]];
            },
            $url
        );
    }
    
    public static function segmentNames(string $url) :array
    {
        $segments = static::segments($url);
        
        return array_map(function ($segment) {
            return trim($segment, '?');
        }, $segments);
    }
    
    public static function segments(string $url_pattern) :array
    {
        preg_match_all('/[^{]+(?=})/', $url_pattern, $matches);
        
        return Arr::flatten($matches);
    }
    
}
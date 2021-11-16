<?php

declare(strict_types=1);

namespace Snicco\Support;

class FilePath
{
    
    /**
     * Ensure path has a trailing slash.
     *
     * @param  string  $path
     * @param  string  $slash
     *
     * @return string
     */
    public static function addTrailingSlash($path, string $slash = DIRECTORY_SEPARATOR) :string
    {
        $path = static::removeTrailingSlash($path);
        
        $path = static::normalize($path, $slash);
        return preg_replace('~'.preg_quote($slash, '~').'*$~', $slash, $path);
    }
    
    /**
     * Ensure path does not have a trailing slash.
     *
     * @param  string  $path
     * @param  string  $slash
     *
     * @return string
     */
    public static function removeTrailingSlash(string $path, string $slash = DIRECTORY_SEPARATOR) :string
    {
        $path = static::normalize($path, $slash);
        
        return preg_replace('~'.preg_quote($slash, '~').'+$~', '', $path);
    }
    
    /**
     * Normalize a path's slashes according to the current OS.
     * Solves mixed slashes that are sometimes returned by WordPress core functions.
     *
     * @param  string  $path
     * @param  string  $slash
     *
     * @return string
     */
    public static function normalize(string $path, string $slash = DIRECTORY_SEPARATOR) :string
    {
        return preg_replace('~['.preg_quote('/\\', '~').']+~', $slash, $path);
    }
    
    public static function ending(string $path, string $ending) :string
    {
        $cleaned_path = preg_replace('/(\.([a-z]+)?)/', '', $path);
        
        return $cleaned_path.'.'.trim($ending, '.');
    }
    
    public static function removeExtensions($file_name) :string
    {
        return Str::beforeLast($file_name, '.');
    }
    
    public static function name($file_path, string $ending = '')
    {
        $name = pathinfo($file_path, PATHINFO_BASENAME);
        
        if ($ending) {
            return Str::before($name, '.'.trim($ending, '.'));
        }
        
        return $name;
    }
    
}

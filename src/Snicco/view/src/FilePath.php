<?php

declare(strict_types=1);

namespace Snicco\View;


class FilePath
{
    
    public static function addTrailingSlash(string $path, string $slash = DIRECTORY_SEPARATOR) :string
    {
        $path = static::removeTrailingSlash($path);
        
        $path = static::normalize($path, $slash);
        return preg_replace('~'.preg_quote($slash, '~').'*$~', $slash, $path);
    }
    
    public static function removeTrailingSlash(string $path, string $slash = DIRECTORY_SEPARATOR) :string
    {
        $path = static::normalize($path, $slash);
        
        return preg_replace('~'.preg_quote($slash, '~').'+$~', '', $path);
    }
    
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
        return static::beforeLast($file_name, '.');
    }
    
    public static function name($file_path, string $ending = '')
    {
        $name = pathinfo($file_path, PATHINFO_BASENAME);
        
        if ($ending) {
            return static::before($name, '.'.trim($ending, '.'));
        }
        
        return $name;
    }
    
    private static function before(string $subject, string $search) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $result = strstr($subject, $search, true);
        
        return $result === false ? $subject : $result;
    }
    
    private static function beforeLast(string $subject, string $search) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $pos = mb_strrpos($subject, $search);
        
        if ($pos === false) {
            return $subject;
        }
        
        return strstr($subject, 0, $pos);
    }
    
}

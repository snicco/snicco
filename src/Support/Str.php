<?php

declare(strict_types=1);

namespace Snicco\Support;

class Str
{
    
    private static array $studly_cache = [];
    
    public static function doesNotEndWith(string $path, string $string) :bool
    {
        return ! static::endsWith($path, $string);
    }
    
    public static function betweenFirst($subject, $from, $to) :string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }
        
        return static::before(static::after($subject, $from), $to);
    }
    
    public static function after(string $subject, string $search) :string
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }
    
    public static function afterLast(string $subject, string $search) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $position = strrpos($subject, $search);
        
        if ($position === false) {
            return $subject;
        }
        
        return substr($subject, $position + strlen($search));
    }
    
    public static function before(string $subject, string $search) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $result = strstr($subject, $search, true);
        
        return $result === false ? $subject : $result;
    }
    
    public static function beforeLast(string $subject, string $search) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $pos = mb_strrpos($subject, $search);
        
        if ($pos === false) {
            return $subject;
        }
        
        return static::substr($subject, 0, $pos);
    }
    
    public static function substr(string $string, int $start, int $length = null) :string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }
    
    public static function between(string $subject, string $from, string $to) :string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }
        
        return static::beforeLast(static::after($subject, $from), $to);
    }
    
    /**
     * @param  string|string[]  $needles
     */
    public static function contains(string $haystack, $needles) :bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function containsAll(string $haystack, array $needles) :bool
    {
        foreach ($needles as $needle) {
            if ( ! static::contains($haystack, $needle)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @param  string|string[]  $needles
     */
    public static function endsWith(string $haystack, $needles) :bool
    {
        foreach ((array) $needles as $needle) {
            if (
                $needle !== '' && $needle !== null
                && substr($haystack, -strlen($needle)) === (string) $needle
            ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @param  string|array  $pattern
     */
    public static function is($pattern, string $value) :bool
    {
        $patterns = Arr::wrap($pattern);
        
        if (empty($patterns)) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            $pattern = (string) $pattern;
            
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern == $value) {
                return true;
            }
            
            $pattern = preg_quote($pattern, '#');
            
            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);
            
            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse a Class[@]method style callback into class and method.
     *
     * @return array<int, string|null>
     */
    public static function parseCallback(string $callback, $default = null) :array
    {
        return static::contains($callback, '@')
            ? explode('@', $callback, 2)
            : [$callback, $default,];
    }
    
    public static function random(int $length = 16) :string
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            
            $bytes = random_bytes($size);
            
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
    
    public static function replaceFirst(string $search, string $replace, string $subject) :string
    {
        if ($search === '') {
            return $subject;
        }
        
        $position = strpos($subject, $search);
        
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        
        return $subject;
    }
    
    /**
     * @param  string|string[]  $needles
     */
    public static function startsWith(string $haystack, $needles) :bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function studly(string $value) :string
    {
        $key = $value;
        
        if (isset(static::$studly_cache[$key])) {
            return static::$studly_cache[$key];
        }
        
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        
        return static::$studly_cache[$key] = str_replace(' ', '', $value);
    }
    
}
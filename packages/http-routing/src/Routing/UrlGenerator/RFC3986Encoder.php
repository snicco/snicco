<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\UrlGenerator;

use function trim;
use function strtr;
use function explode;
use function implode;
use function is_null;
use function array_map;
use function array_flip;
use function array_diff;
use function rawurlencode;
use function http_build_query;

/**
 * https://datatracker.ietf.org/doc/html/rfc3986
 * @interal
 */
final class RFC3986Encoder implements UrlEncoder
{
    
    const RFC3986_UNRESERVED = [
        '-' => '-',
        '.' => '.',
        '_' => '_',
        '~' => '~',
    ];
    
    const RFC3986_SUB_DELIMITERS = [
        '!' => '%21',
        '$' => '%24',
        '&' => '%26',
        "'" => '%27',
        '*' => '%2A',
        '+' => '%2B',
        ',' => '%2C',
        ';' => '%3B',
        '=' => '%3D',
        '(' => '%28',
        ')' => '%29',
    ];
    
    const RFC3986_PCHARS = self::RFC3986_UNRESERVED + self::RFC3986_SUB_DELIMITERS + [
        '@' => '%40',
        ':' => '%3A',
    ];
    
    private const QUERY_FRAGMENT_EXTRA = [
        '/' => '%2F',
        '?' => '%3F',
    ];
    
    /**
     * @var array<string,string>
     */
    private $query_special;
    
    public function __construct(?array $query_special = null)
    {
        $this->query_special = is_null($query_special)
            ? [
                '=' => '%3D',
                '&' => '%26',
            ]
            : $query_special;
    }
    
    public function encodeQuery(array $query) :string
    {
        if ($query === []) {
            return '';
        }
        
        $encoded_query = http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
        
        if ($encoded_query === '') {
            return '';
        }
        
        $allowed_in_query = array_diff(
            self::RFC3986_PCHARS + self::QUERY_FRAGMENT_EXTRA,
            $this->query_special
        );
        
        return strtr($encoded_query, array_flip($allowed_in_query));
    }
    
    public function encodePath(string $path) :string
    {
        $path = implode(
            '/',
            array_map('rawurlencode', explode('/', $path))
        );
        
        return strtr($path, array_flip(self::RFC3986_PCHARS));
    }
    
    public function encodeFragment(string $fragment) :string
    {
        $fragment = trim($fragment, '#');
        $encoded_fragment = rawurlencode($fragment);
        
        $allowed_in_fragment = self::RFC3986_PCHARS + self::QUERY_FRAGMENT_EXTRA;
        
        return strtr($encoded_fragment, array_flip($allowed_in_fragment));
    }
    
}
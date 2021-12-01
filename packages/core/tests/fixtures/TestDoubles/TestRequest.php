<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\TestDoubles;

use Snicco\Http\Psr7\Request;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * @internal
 * @todo Remove
 */
class TestRequest extends Request
{
    
    public $body;
    
    public static function fromFullUrl(string $method, string $url) :TestRequest
    {
        $psr17Factory = new Psr17Factory();
        
        $request = new TestRequest(
            $psr17Factory->createServerRequest($method, $url, ['REQUEST_METHOD' => $method])
        );
        
        parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query);
        
        return $request->withAttribute('_wp_admin_folder', 'wp-admin');
    }
    
    public static function from(string $method, $path, $host = null) :TestRequest
    {
        $psr17Factory = new Psr17Factory();
        
        $path = ltrim($path, '/');
        $method = strtoupper($method);
        
        $host = $host ?? 'https://foo.com';
        $url = trim($host, '/').'/'.$path;
        
        $request = new TestRequest(
            $psr17Factory->createServerRequest(
                $method,
                $url,
                ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => 'index.php']
            )
        );
        parse_str($request->getUri()->getQuery(), $query);
        $request = $request->withQueryParams($query);
        
        return $request->withAttribute('_wp_admin_folder', 'wp-admin');
    }
    
    public static function withServerParams(Request $request, array $params) :TestRequest
    {
        $psr17Factory = new Psr17Factory();
        
        $request = new TestRequest(
            $psr17Factory->createServerRequest($request->getMethod(), $request->getUri(), $params)
        );
        
        return $request->withAttribute('_wp_admin_folder', 'wp-admin');
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\ExceptionHandling\Exceptions\AuthorizationException;

class Authorize extends Middleware
{
    
    private string  $capability;
    private ?int    $object_id;
    private ?string $key;
    
    public function __construct(string $capability = 'manage_options', string $object_id = null, string $key = null)
    {
        $this->capability = $capability;
        $this->object_id = (int) $object_id;
        $this->key = $key;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $args = [];
        if ($this->object_id) {
            $args[] = intval($this->object_id);
        }
        if ($this->key) {
            $args[] = $this->key;
        }
        
        if (WP::currentUserCan($this->capability, ...$args)) {
            return $next($request);
        }
        
        throw new AuthorizationException(
            "Authorization failed for required capability $this->capability"
        );
    }
    
}

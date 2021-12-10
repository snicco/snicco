<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use function hash;
use function strlen;
use function bin2hex;
use function ctype_alnum;
use function random_bytes;

/**
 * @api
 */
final class SessionId
{
    
    public static $token_strength = 40;
    
    /**
     * @var string
     */
    private $id_as_string;
    
    private function __construct(string $id_as_string = '')
    {
        if ( ! ctype_alnum($id_as_string)
             || strlen($id_as_string) !== 2 * SessionId::$token_strength) {
            $id_as_string = $this->newString();
        }
        
        $this->id_as_string = $id_as_string;
    }
    
    public static function fromCookieId(string $id) :SessionId
    {
        return new SessionId($id);
    }
    
    public static function createFresh() :SessionId
    {
        return new SessionId('');
    }
    
    public function asString() :string
    {
        return $this->id_as_string;
    }
    
    public function __toString()
    {
        return $this->asString();
    }
    
    public function asHash() :string
    {
        return hash('sha256', $this->asString());
    }
    
    public function sameAs(SessionId $id2) :bool
    {
        return $id2->asString() === $this->asString();
    }
    
    private function newString() :string
    {
        return bin2hex(random_bytes(static::$token_strength));
    }
    
}
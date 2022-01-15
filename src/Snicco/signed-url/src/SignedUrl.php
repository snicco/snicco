<?php

declare(strict_types=1);

namespace Snicco\SignedUrl;

use Webmozart\Assert\Assert;

use function time;

/**
 * @api
 */
final class SignedUrl
{
    
    const EXPIRE_KEY = 'expires';
    const SIGNATURE_KEY = 'signature';
    
    /**
     * @var string
     */
    private $link_target;
    
    /**
     * @var string
     */
    private $identifier;
    
    /**
     * @var int
     */
    private $expires_at;
    
    /**
     * @var int
     */
    private $max_usage;
    
    /**
     * @var string
     */
    private $protects;
    
    private function __construct(string $link_target, string $protects, string $identifier, int $expires_at, int $max_usage)
    {
        $this->link_target = $link_target;
        $this->identifier = $identifier;
        $this->expires_at = $expires_at;
        $this->max_usage = $max_usage;
        $this->protects = $protects;
    }
    
    public static function create(string $link_target, string $protects, string $identifier, int $expires_at, int $max_usage) :SignedUrl
    {
        Assert::notEmpty($link_target);
        Assert::notEmpty($protects);
        Assert::notEmpty($identifier);
        Assert::greaterThan($expires_at, time());
        Assert::greaterThanEq($max_usage, 1);
        
        return new SignedUrl($link_target, $protects, $identifier, $expires_at, $max_usage);
    }
    
    public function asString() :string
    {
        return $this->link_target;
    }
    
    public function __toString() :string
    {
        return $this->link_target;
    }
    
    public function identifier() :string
    {
        return $this->identifier;
    }
    
    public function expiresAt() :int
    {
        return $this->expires_at;
    }
    
    public function maxUsage() :int
    {
        return $this->max_usage;
    }
    
    public function protects() :string
    {
        return $this->protects;
    }
    
}
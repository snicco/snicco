<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use Webmozart\Assert\Assert;
use ParagonIE\ConstantTime\Hex;
use ParagonIE\ConstantTime\Binary;

use function random_bytes;

/**
 * @api
 */
final class Secret
{
    
    private string $hex_encoded;
    
    private function __construct(string $hex_encoded)
    {
        $this->hex_encoded = $hex_encoded;
    }
    
    public static function generate(int $strength = 32) :Secret
    {
        Assert::greaterThanEq($strength, 16, "Minimum strength of 16 bytes is required.");
        
        $bytes = random_bytes($strength);
        return new Secret($strength.'|'.Hex::encode($bytes));
    }
    
    public static function fromHexEncoded(string $string) :Secret
    {
        // This is not foolproof and just a quick check to catch obviously wrong secrets.
        $parts = explode('|', $string);
        
        Assert::count($parts, 2, "Your stored secret seems to be malformed.");
        Assert::integerish($parts[0], 'Your stored secret seems to be malformed.');
        Assert::stringNotEmpty($parts[1], 'Your stored secret seems to be malformed.');
        
        Assert::same(Binary::safeStrlen($parts[1]), intval($parts[0]) * 2);
        return new self($string);
    }
    
    public function asString() :string
    {
        return $this->hex_encoded;
    }
    
    /**
     * @interal
     */
    public function asBytes() :string
    {
        if ( ! isset($this->as_bytes)) {
            [$length, $hex_encoded] = explode('|', $this->hex_encoded);
            $this->as_bytes = Hex::decode($hex_encoded);
        }
        
        return $this->as_bytes;
    }
    
}
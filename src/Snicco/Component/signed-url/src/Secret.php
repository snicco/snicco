<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use Exception;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\ConstantTime\Hex;
use Webmozart\Assert\Assert;

use function random_bytes;

final class Secret
{
    private string $hex_encoded;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private string $as_bytes;

    private function __construct(string $hex_encoded)
    {
        $this->hex_encoded = $hex_encoded;
    }

    /**
     * @throws Exception
     */
    public static function generate(int $strength = 32): Secret
    {
        Assert::greaterThanEq($strength, 16, 'Minimum strength of 16 bytes is required.');

        /** @var positive-int $strength */
        $bytes = random_bytes($strength);

        return new Secret((string) $strength . '|' . Hex::encode($bytes));
    }

    public static function fromHexEncoded(string $string): Secret
    {
        // This is not foolproof and just a quick check to catch obviously wrong secrets.
        /** @var array{0:string, 1:string} $parts */
        $parts = explode('|', $string);

        Assert::count($parts, 2, 'Your stored secret seems to be malformed.');
        Assert::integerish($parts[0], 'Your stored secret seems to be malformed.');
        Assert::stringNotEmpty($parts[1], 'Your stored secret seems to be malformed.');

        Assert::same(Binary::safeStrlen($parts[1]), (int) ($parts[0]) * 2);

        return new self($string);
    }

    public function asString(): string
    {
        return $this->hex_encoded;
    }

    /**
     * @internal
     *
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function asBytes(): string
    {
        if (! isset($this->as_bytes)) {
            $parts = explode('|', $this->hex_encoded);
            $this->as_bytes = Hex::decode($parts[1]);
        }

        return $this->as_bytes;
    }
}

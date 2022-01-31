<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use Snicco\Component\StrArr\Str;

use function ctype_alnum;
use function hash;
use function strlen;

/**
 * @api
 */
final class SessionId
{

    public static int $token_strength = 32;
    private string $id_as_string;

    private function __construct(string $id_as_string = '')
    {
        if (!ctype_alnum($id_as_string)
            || strlen($id_as_string) !== 2 * SessionId::$token_strength) {
            $id_as_string = $this->newString();
        }

        $this->id_as_string = $id_as_string;
    }

    private function newString(): string
    {
        return Str::random(self::$token_strength);
    }

    public static function fromCookieId(string $id): SessionId
    {
        return new SessionId($id);
    }

    public static function createFresh(): SessionId
    {
        return new SessionId('');
    }

    public function __toString()
    {
        return $this->asString();
    }

    public function asString(): string
    {
        return $this->id_as_string;
    }

    public function asHash(): string
    {
        return hash('sha256', $this->asString());
    }

    public function sameAs(SessionId $id2): bool
    {
        return $id2->asString() === $this->asString();
    }

}
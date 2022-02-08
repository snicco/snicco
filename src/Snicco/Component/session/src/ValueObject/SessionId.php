<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use RuntimeException;
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
        $res = hash('sha256', $this->asString());
        if (false === $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not create hash for session id.');
            // @codeCoverageIgnoreEnd
        }
        return $res;
    }

    public function sameAs(SessionId $id2): bool
    {
        return $id2->asString() === $this->asString();
    }

    private function newString(): string
    {
        $strength = max(self::$token_strength, 16);
        return Str::random($strength);
    }

}
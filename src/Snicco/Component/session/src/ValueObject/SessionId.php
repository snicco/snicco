<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;

use function explode;
use function random_bytes;

final class SessionId
{
    private string $selector;

    private string $validator;

    private function __construct(string $selector, string $verifier)
    {
        if (24 !== Binary::safeStrlen($selector) || 24 !== Binary::safeStrlen($verifier)) {
            [$selector, $verifier] = $this->newTokens();
        }

        $this->selector = $selector;
        $this->validator = $verifier;
    }

    public function __toString()
    {
        return $this->asString();
    }

    public static function fromCookieId(string $session_id_from_cookie): SessionId
    {
        $parts = explode('|', $session_id_from_cookie, 2);

        return new SessionId($parts[0] ?? '', $parts[1] ?? '');
    }

    public static function new(): SessionId
    {
        return new SessionId('', '');
    }

    public function asString(): string
    {
        return $this->selector . '|' . $this->validator;
    }

    public function sameAs(SessionId $id2): bool
    {
        return $id2->asString() === $this->asString();
    }

    public function selector(): string
    {
        return $this->selector;
    }

    public function validator(): string
    {
        return $this->validator;
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function newTokens(): array
    {
        $selector = Base64UrlSafe::encode(random_bytes(16));
        $validator = Base64UrlSafe::encode(random_bytes(16));

        return [$selector, $validator];
    }
}

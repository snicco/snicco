<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use Exception;
use InvalidArgumentException;
use LogicException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Webmozart\Assert\Assert;

use function ltrim;
use function parse_str;
use function parse_url;
use function random_bytes;
use function rtrim;
use function strpos;
use function time;

final class UrlSigner
{
    private SignedUrlStorage $storage;

    private HMAC $hasher;

    public function __construct(SignedUrlStorage $storage, HMAC $hasher)
    {
        $this->storage = $storage;
        $this->hasher = $hasher;
    }

    /**
     * @param non-empty-string $target
     * @param positive-int     $lifetime_in_sec
     * @param positive-int     $max_usage
     *
     * @throws UnavailableStorage
     * @throws Exception          if random_bytes can't be generated
     */
    public function sign(
        string $target,
        int $lifetime_in_sec,
        int $max_usage = 1,
        string $request_context = ''
    ): SignedUrl {
        Assert::notEmpty($target);

        $target = $this->normalizeTarget($target);

        $parts = $this->parseUrl($target);

        $path = $this->getPath($parts);
        $query = $this->getQueryString($parts);
        $expires_at = $this->getExpiryTimestamp($lifetime_in_sec);

        $path_with_query = $query ? ($path . '?' . $query) : $path;

        // We create a random identifier for storing the signed url usage limit.
        // We don't store the signature or a hash of it.
        $identifier = Base64UrlSafe::encode(random_bytes(16));

        // The signature consists of the identifier, the request context the developer passed
        // (such as ip address or user agent) and the protected path with an expires_at query parameter.
        $plain_text_signature =
            $identifier .
            $request_context .
            $this->appendExpiryQueryParam($path_with_query, $expires_at);

        $signature = Base64UrlSafe::encode($this->hasher->create($plain_text_signature));

        // We append the expires_at and signature timestamp to the path, so that it can be easily validated.
        // If any of the plain text parts have been tampered validation wil fail.
        $path_with_query =
            $this->appendExpiryQueryParam($path_with_query, $expires_at)
            . '&'
            . SignedUrl::SIGNATURE_KEY
            . '='
            . $identifier
            . '|'
            . $signature;

        $url = ($domain_and_scheme = $this->getDomainAndSchema($parts))
            ? $domain_and_scheme . $path_with_query
            : $path_with_query;

        $signed_url = SignedUrl::create($url, $target, $identifier, $expires_at, $max_usage);

        $this->storage->store($signed_url);

        return $signed_url;
    }

    private function normalizeTarget(string $protect): string
    {
        if (0 === strpos($protect, 'http')) {
            return $protect;
        }

        return '/' . ltrim($protect, '/');
    }

    private function parseUrl(string $protect): array
    {
        $parts = ($parts = parse_url($protect)) ? $parts : [];

        if (! isset($parts['path']) && isset($parts['host'], $parts['scheme'])) {
            $parts['path'] = '/';
        }

        $this->validateUrlParts($parts, $protect);

        return $parts;
    }

    private function validateUrlParts(array $parsed, string $target): void
    {
        if ('/' === $parsed['path'] && '/' !== $target && 0 !== strpos($target, 'http')) {
            throw new InvalidArgumentException("{$target} is not a valid path.");
        }

        /** @var string $qs */
        $qs = $parsed['query'] ?? '';

        parse_str($qs, $query);

        if (isset($query[SignedUrl::EXPIRE_KEY])) {
            throw new LogicException(
                "The expires query parameter is reserved.\nPlease rename your query parameter."
            );
        }

        if (isset($query[SignedUrl::SIGNATURE_KEY])) {
            throw new LogicException(
                "The signature query parameter is reserved.\nPlease rename your query parameter."
            );
        }
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    private function getPath(array $parts): string
    {
        if (isset($parts['path'])) {
            return $parts['path'];
        }

        // Should not be possible ever.
        // @codeCoverageIgnoreStart
        throw new InvalidArgumentException('Invalid path provided.');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private function getQueryString(array $parts): ?string
    {
        if (! isset($parts['query'])) {
            return null;
        }

        return rtrim($parts['query'], '&');
    }

    private function getExpiryTimestamp(int $lifetime_in_sec): int
    {
        return time() + $lifetime_in_sec;
    }

    private function appendExpiryQueryParam(string $path, int $expires_at): string
    {
        if (! strpos($path, '?')) {
            return $path . '?' . SignedUrl::EXPIRE_KEY . '=' . (string) $expires_at;
        }

        return rtrim($path, '&') . SignedUrl::EXPIRE_KEY . '=' . (string) $expires_at;
    }

    /**
     * @psalm-suppress MixedOperand
     */
    private function getDomainAndSchema(array $parts): ?string
    {
        if (isset($parts['host'], $parts['scheme'])) {
            return $parts['scheme'] . '://' . $parts['host'];
        }

        return null;
    }
}

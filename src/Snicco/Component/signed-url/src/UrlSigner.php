<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use InvalidArgumentException;
use LogicException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\Hasher\Hasher;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Webmozart\Assert\Assert;

use function ltrim;
use function parse_str;
use function parse_url;
use function random_bytes;
use function rtrim;
use function strpos;
use function time;

/**
 * @api
 */
final class UrlSigner
{

    private SignedUrlStorage $storage;
    private Hasher $hasher;

    public function __construct(
        SignedUrlStorage $storage,
        Hasher $hasher
    ) {
        $this->storage = $storage;
        $this->hasher = $hasher;
    }

    /**
     * @throws UnavailableStorage
     */
    public function sign(
        string $protect,
        int $lifetime_in_sec,
        int $max_usage = 1,
        string $request_context = ''
    ): SignedUrl {
        Assert::notEmpty($protect);

        $protect = $this->normalizeProtect($protect);

        $parts = $this->parseUrl($protect);

        $path = $this->getPath($parts);
        $query = $this->getQueryString($parts);
        $expires_at = $this->getExpiryTimestamp($lifetime_in_sec);

        $path_with_query = $query ? ($path . '?' . $query) : $path;

        // We create a random identifier for storing the signed url usage limit.
        // We don't store the signature or a hash of it.
        // We do this, so that a read-only sql injection does allow access protected urls.
        // (Assuming the filesystem is not compromised)
        $identifier = Base64UrlSafe::encode(random_bytes(16));

        // The signature consists of the identifier, the request context the developer passed
        // (such as ip address or user agent) and the protected path with an expires_at query parameter.
        $plain_text_signature = $identifier .
            $request_context .
            $this->appendExpiryQueryParam($path_with_query, $expires_at);

        $signature = Base64UrlSafe::encode($this->hasher->hash($plain_text_signature));

        // We append the expires_at and signature timestamp to the path, so that it can be easily validated.
        // If any of the plain text parts have been tampered validation wil fail.
        $path_with_query = $this->appendExpiryQueryParam($path_with_query, $expires_at)
            . '&' . SignedUrl::SIGNATURE_KEY . '='
            . $identifier
            . '|'
            . $signature;

        $url = ($domain_and_scheme = $this->getDomainAndSchema($parts))
            ? $domain_and_scheme . $path_with_query
            : $path_with_query;

        $signed_url = SignedUrl::create(
            $url,
            $protect,
            $identifier,
            $expires_at,
            $max_usage
        );

        $this->storage->store($signed_url);

        return $signed_url;
    }

    private function normalizeProtect(string $protect): string
    {
        if (strpos($protect, 'http') !== 0) {
            $protect = '/' . ltrim($protect, '/');
        }
        return $protect;
    }

    private function parseUrl(string $protect): array
    {
        $parts = ($parts = parse_url($protect)) ? $parts : [];

        if (!isset($parts['path']) && isset($parts['host']) && isset($parts['scheme'])) {
            $parts['path'] = '/';
        }

        $this->validateUrlParts($parts, $protect);

        return $parts;
    }

    private function validateUrlParts(array $parsed, string $protect): void
    {
        if ($parsed['path'] === '/' && $protect !== '/' && strpos($protect, 'http') !== 0) {
            throw new InvalidArgumentException(
                "$protect is not a valid path."
            );
        }

        if (strpos($protect, '/') !== 0) {
            // it's not a pass, so we assume it's an absolute url.
            if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
                throw new InvalidArgumentException("$protect is not a valid url.");
            }
        }

        parse_str($parsed['query'] ?? '', $query);

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

    private function getPath(array $parts): string
    {
        if (isset($parts['path'])) {
            return $parts['path'];
        }

        throw new InvalidArgumentException(
            'Invalid path provided.'
        );
    }

    private function getQueryString(array $parts): ?string
    {
        if (!isset($parts['query'])) {
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
        if (!strpos($path, '?')) {
            return $path . '?' . SignedUrl::EXPIRE_KEY . '=' . $expires_at;
        }

        return rtrim($path, '&') . SignedUrl::EXPIRE_KEY . '=' . $expires_at;
    }

    private function getDomainAndSchema(array $parts): ?string
    {
        if (isset($parts['host']) && isset($parts['scheme'])) {
            return $parts['scheme'] . '://' . $parts['host'];
        }

        return null;
    }

}
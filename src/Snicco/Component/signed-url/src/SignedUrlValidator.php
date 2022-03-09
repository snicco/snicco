<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Exception\InvalidSignature;
use Snicco\Component\SignedUrl\Exception\SignedUrlExpired;
use Snicco\Component\SignedUrl\Exception\SignedUrlUsageExceeded;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

use function ltrim;
use function parse_str;
use function parse_url;
use function preg_replace;
use function rtrim;

final class SignedUrlValidator
{
    private SignedUrlStorage $storage;

    private Clock $clock;

    private HMAC $hmac;

    public function __construct(SignedUrlStorage $storage, HMAC $hmac, Clock $clock = null)
    {
        $this->storage = $storage;
        $this->hmac = $hmac;
        $this->clock = $clock ?? SystemClock::fromUTC();
    }

    /**
     * @param string $request_target $psr_request->getRequestTarget() ||
     *     $_SERVER['PATHINFO].?$_SERVER['QUERY_STRING']
     * @param string $request_context Any additional request context to check against.
     *
     * @throws InvalidSignature
     * @throws SignedUrlExpired
     * @throws SignedUrlUsageExceeded
     * @throws UnavailableStorage
     */
    public function validate(string $request_target, string $request_context = ''): void
    {
        [$path, $query_string, $query_as_array] = $this->parse(
            $request_target
        );

        if (! isset($query_as_array[SignedUrl::SIGNATURE_KEY])) {
            throw new InvalidSignature("Missing signature parameter for path [$path].");
        }

        if (! isset($query_as_array[SignedUrl::EXPIRE_KEY])) {
            throw new InvalidSignature("Missing expires parameter for path [$path].");
        }

        $arr = explode('|', $query_as_array[SignedUrl::SIGNATURE_KEY]);
        $identifier = (string) ($arr[0] ?? '');
        $provided_signature = (string) ($arr[1] ?? '');

        // Rebuild the parts from the provided url
        // if anything has been changed at all the resulting signature will not match the
        // signature query parameter.
        $plaint_text_signature =
            $identifier .
            $request_context .
            $path .
            '?' .
            $this->queryStringWithoutSignature($query_string);

        $expected_signature = Base64UrlSafe::encode(
            $this->hmac->create($plaint_text_signature)
        );

        $this->validateSignature($expected_signature, $provided_signature, $path);
        $this->validateExpiration((int) ($query_as_array[SignedUrl::EXPIRE_KEY] ?? 0), $path);
        $this->validateUsage($identifier, $path);
    }

    /**
     * @return array{0:string, 1:string, 2: array<string,string>}
     */
    private function parse(string $path_with_query_string): array
    {
        $parts = (array) parse_url($path_with_query_string);

        $path = $parts['path'] ?? '';
        $query_string = $parts['query'] ?? '';
        parse_str($query_string, $query_as_array);

        /** @var array<string,string> $query_as_array */
        return [$path, $query_string, $query_as_array];
    }

    private function queryStringWithoutSignature(string $query_string): string
    {
        $qs = preg_replace(
            '/(^|&)' . SignedUrl::SIGNATURE_KEY . '=[^&]+/',
            '',
            $query_string
        );

        if (null === $qs) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("preg_replace returned null for query_string [$query_string].");
            // @codeCoverageIgnoreEnd
        }

        $str = rtrim($qs, '&');

        return ltrim($str, '?');
    }

    private function validateExpiration(int $expires, string $path): void
    {
        $diff = $expires - $this->clock->currentTimestamp();

        if ($diff < 0) {
            $diff = abs($diff);
            throw new SignedUrlExpired("Signed url expired by [$diff] seconds for path [$path].");
        }
    }

    private function validateSignature(string $expected_signature, string $provided_signature, string $path): void
    {
        if (! hash_equals($expected_signature, $provided_signature)) {
            throw new InvalidSignature("Invalid signature for path [$path].");
        }
    }

    private function validateUsage(string $identifier, string $path): void
    {
        try {
            $this->storage->consume($identifier);
        } catch (BadIdentifier $e) {
            throw new SignedUrlUsageExceeded(
                "Signed url usages exceeded for path [$path].",
                $e->getCode(),
                $e
            );
        }
    }
}

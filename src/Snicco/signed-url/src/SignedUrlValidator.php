<?php

declare(strict_types=1);

namespace Snicco\SignedUrl;

use Snicco\SignedUrl\Contracts\Hasher;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;
use Snicco\SignedUrl\Exceptions\SignedUrlExpired;
use Snicco\SignedUrl\Exceptions\InvalidSignature;
use Snicco\SignedUrl\Exceptions\SignedUrlUsageExceeded;

use function rtrim;
use function ltrim;
use function parse_str;
use function parse_url;
use function preg_replace;

/**
 * @api
 */
final class SignedUrlValidator
{
    
    /**
     * @var SignedUrlStorage
     */
    private $storage;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    /**
     * @var Hasher
     */
    private $hasher;
    
    public function __construct(SignedUrlStorage $storage, Hasher $hasher, SignedUrlClock $clock = null)
    {
        $this->storage = $storage;
        $this->hasher = $hasher;
        $this->clock = $clock ?? new SignedUrlClockUsingDateTimeImmutable();
    }
    
    /**
     * @param  string  $request_target  $psr->request->getRequestTarget() or
     *     $_SERVER['PATHINFO].?$_SERVER['QUERY_STRING']
     * @param  string  $request_context  Any additional request context to check against.
     *
     * @throws InvalidSignature
     * @throws SignedUrlExpired
     * @throws SignedUrlUsageExceeded
     */
    public function validate(string $request_target, string $request_context = '') :void
    {
        [$path, $query_string, $query_as_array] = $this->parse(
            $request_target
        );
        
        $arr = explode('|', $query_as_array[SignedUrl::SIGNATURE_KEY] ?? '');
        $identifier = $arr[0] ?? '';
        $provided_signature = $arr[1] ?? '';
        
        // Rebuild the parts from the provided url
        // if anything has been changed at all the resulting signature will not match the
        // signature query parameter.
        $plaint_text_signature = $identifier.
                                 $request_context.
                                 $path.'?'.$this->queryStringWithoutSignature($query_string);
        
        $expected_signature = Base64UrlSafe::encode(
            $this->hasher->hash($plaint_text_signature)
        );
    
        $this->validateExpiration(intval($query_as_array[SignedUrl::EXPIRE_KEY] ?? 0), $path);
        $this->validateSignature($expected_signature, $provided_signature, $path);
        $this->validateUsage($identifier, $path);
        
        $this->storage->decrementUsage($identifier);
    }
    
    private function queryStringWithoutSignature($query_string) :string
    {
        $str = rtrim(
            preg_replace(
                '/(^|&)signature=[^&]+/',
                '',
                $query_string
            ),
            '&'
        );
        
        return ltrim($str, '?');
    }
    
    private function parse(string $path_with_query_string) :array
    {
        $parts = (array) parse_url($path_with_query_string);
        
        $path = $parts['path'] ?? '';
        $query_string = $parts['query'] ?? '';
        parse_str($query_string, $query_as_array);
        return [$path, $query_string, $query_as_array];
    }
    
    private function validateSignature(string $expected_signature, string $provided_signature, string $path) :void
    {
        if ( ! hash_equals($expected_signature, $provided_signature)) {
            throw new InvalidSignature("Invalid signature for path [$path].");
        }
    }
    
    private function validateExpiration(int $expires, string $path) :void
    {
        $diff = $expires - $this->clock->currentTimestamp();
    
        if ($diff < 0) {
            $diff = abs($diff);
            throw new SignedUrlExpired("Signed url expired by [$diff] seconds for path [$path].");
        }
    }
    
    private function validateUsage(string $hashed_signature, $path) :void
    {
        if ($this->storage->remainingUsage($hashed_signature) === 0) {
            throw new SignedUrlUsageExceeded(
                "Signed url usages exceeded for path [$path]."
            );
        }
    }
    
}
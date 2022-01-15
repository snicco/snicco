<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Contracts;

use RuntimeException;
use Snicco\SignedUrl\SignedUrl;
use Snicco\SignedUrl\Exceptions\BadIdentifier;

interface SignedUrlStorage
{
    
    /**
     * Decrement the number of left usages for the signed url by one.
     * Remove the signed url from storage if the new left usage is zero.
     *
     * @throws RuntimeException If the usage can not be updated.
     * @throws BadIdentifier If the identifier does not exist at all.
     */
    public function decrementUsage(string $identifier) :void;
    
    /**
     * The number of usages left. Has to return 0 if the hashed_signature does not exist.
     *
     * @throws RuntimeException If retrieval failed or the left usages fails for any reason.
     */
    public function remainingUsage(string $identifier) :int;
    
    /**
     * @throws RuntimeException If the signed url can't be stored
     */
    public function store(SignedUrl $signed_url) :void;
    
    /**
     * Remove all expired signed links.
     *
     * @throws RuntimeException If the magic link can't be stored
     */
    public function gc() :void;
    
}
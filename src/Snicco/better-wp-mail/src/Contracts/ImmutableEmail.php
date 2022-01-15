<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Attachment;
use Snicco\Mail\Exceptions\MissingContentIdException;

/**
 * @api
 */
interface ImmutableEmail
{
    
    /**
     * @return Attachment[]
     */
    public function getAttachments() :array;
    
    /**
     * @return Address[]
     */
    public function getBcc() :array;
    
    /**
     * @return Address[]
     */
    public function getCc() :array;
    
    /**
     * @return Address[]
     */
    public function getTo() :array;
    
    /**
     * @return Address[]
     */
    public function getFrom() :array;
    
    /**
     * @return resource|string|null
     */
    public function getHtmlBody();
    
    public function getHtmlCharset() :?string;
    
    /**
     * @return Address[]
     */
    public function getReplyTo() :array;
    
    public function getPriority() :?int;
    
    public function getReturnPath() :?Address;
    
    public function getSender() :?Address;
    
    public function getSubject() :string;
    
    /**
     * @return resource|string|null
     */
    public function getTextBody();
    
    public function getTextCharset() :?string;
    
    /**
     * @return array<string,string> Not validated.
     */
    public function getCustomHeaders() :array;
    
    /**
     * @param  string  $filename
     *
     * @return string
     * @throws MissingContentIdException
     */
    public function getCid(string $filename) :string;
    
    public function getContext() :array;
    
    public function getHtmlTemplate() :?string;
    
    public function getTextTemplate() :?string;
    
}
<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\ReplyTo;
use Snicco\Mail\ValueObjects\Attachment;

/**
 * @api
 */
interface ImmutableEmail
{
    
    /**
     * @return From
     */
    public function getFrom() :From;
    
    /**
     * @return ReplyTo
     */
    public function getReplyTo() :ReplyTo;
    
    /**
     * @return string
     */
    public function getContentType() :string;
    
    /**
     * @return string
     */
    public function getSubject() :string;
    
    /**
     * @return string
     */
    public function getMessage() :string;
    
    /**
     * @return Attachment[]
     */
    public function getAttachments() :array;
    
}
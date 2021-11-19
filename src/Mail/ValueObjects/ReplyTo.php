<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
final class ReplyTo extends Name
{
    
    /**
     * @var string
     */
    protected $prefix = 'Reply-To';
    
}
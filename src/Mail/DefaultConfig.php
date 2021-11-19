<?php

declare(strict_types=1);

namespace Snicco\Mail;

use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\ReplyTo;

/**
 * @api
 */
final class DefaultConfig
{
    
    /**
     * @var string
     */
    private $from_name;
    
    /**
     * @var string
     */
    private $from_email;
    
    /**
     * @var string
     */
    private $reply_to_name;
    
    /**
     * @var string
     */
    private $reply_to_email;
    
    public function __construct(string $from_name, string $from_email, string $reply_to_name, string $reply_to_email)
    {
        $this->from_name = $from_name;
        $this->from_email = $from_email;
        $this->reply_to_name = $reply_to_name;
        $this->reply_to_email = $reply_to_email;
    }
    
    public static function fromWordPressSettings() :DefaultConfig
    {
        return new DefaultConfig(
            $name = get_bloginfo('site_name'),
            $email = get_bloginfo('admin_email'),
            $name,
            $email,
        );
    }
    
    public function getFrom() :From
    {
        return new From($this->from_email, $this->from_name);
    }
    
    public function getReplyTo() :ReplyTo
    {
        return new ReplyTo($this->reply_to_email, $this->reply_to_name);
    }
    
}
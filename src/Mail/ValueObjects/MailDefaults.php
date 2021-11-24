<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

use function apply_filters;

/**
 * @api
 */
final class MailDefaults
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
    
    public function __construct(string $from_email, string $from_name, string $reply_to_email, string $reply_to_name)
    {
        $this->from_email = apply_filters('wp_mail_from', $from_email);
        $this->from_name = apply_filters('wp_mail_from_name', $from_name);
        $this->reply_to_name = $reply_to_name;
        $this->reply_to_email = $reply_to_email;
    }
    
    public static function fromWordPressSettings() :MailDefaults
    {
        $email = apply_filters('wp_mail_from', get_bloginfo('admin_email'));
        $name = apply_filters('wp_mail_from_name', get_bloginfo('name'));
        
        return new MailDefaults(
            $email,
            $name,
            $email,
            $name,
        );
    }
    
    public function getFrom() :Address
    {
        return Address::create([$this->from_email, $this->from_name]);
    }
    
    public function getReplyTo() :Address
    {
        return Address::create([$this->reply_to_email, $this->reply_to_name]);
    }
    
}
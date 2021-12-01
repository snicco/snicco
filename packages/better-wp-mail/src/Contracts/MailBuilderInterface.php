<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use WP_User;
use Snicco\Mail\Email;

/**
 * @api
 */
interface MailBuilderInterface
{
    
    /**
     * @note The received email is NOT the same instance that is being sent.
     * @throws TransportException
     */
    public function send(Email $mail) :void;
    
    /**
     * @param  string|array<string,string>|WP_User|WP_User[]|array<array<string,string>>  $addresses
     *
     * @return MailBuilderInterface
     */
    public function to($addresses) :MailBuilderInterface;
    
    /**
     * @param  string|array<string,string>|WP_User|WP_User[]|array<array<string,string>>  $addresses
     *
     * @return MailBuilderInterface
     */
    public function cc($addresses) :MailBuilderInterface;
    
    /**
     * @param  string|array<string,string>|WP_User|WP_User[]|array<array<string,string>>  $addresses
     *
     * @return MailBuilderInterface
     */
    public function bcc($addresses) :MailBuilderInterface;
    
}
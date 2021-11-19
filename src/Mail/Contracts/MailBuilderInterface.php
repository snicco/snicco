<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\Email;

/**
 * @api
 */
interface MailBuilderInterface
{
    
    public function send(Email $mail);
    
    public function to($recipients) :MailBuilderInterface;
    
    public function cc($recipients) :MailBuilderInterface;
    
    public function bcc($recipients) :MailBuilderInterface;
    
}
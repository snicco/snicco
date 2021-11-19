<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Email;

class DiscountMail extends Email
{
    
    public string $product;
    
    public function __construct($product)
    {
        $this->product = $product;
    }
    
    public function configure() :Email
    {
        return $this
            ->text('mails.new_discount')
            ->subject('We have a new discount');
    }
    
    public function unique() :bool
    {
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Mailable;
use Snicco\Routing\UrlGenerator;

class ConfirmAccountTestMail extends Mailable
{
    
    public function build(UrlGenerator $g) :Mailable
    {
        return $this->subject('Confirm your account')
                    ->text('mails.confirm-account-email')
                    ->with([
                        'confirm_url' => $g->to('/foo'),
                    ]);
    }
    
    public function unique() :bool
    {
        return true;
    }
    
}
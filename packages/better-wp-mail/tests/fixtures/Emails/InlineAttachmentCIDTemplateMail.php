<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

final class InlineAttachmentCIDTemplateMail extends Email
{
    
    public function configure()
    {
        $this->subject('foo')->text('öö')
             ->htmlTemplate(dirname(__DIR__, 2).'/fixtures/inline-attachment.php')
             ->embedFromPath(
                 dirname(__DIR__, 2).'/fixtures/php-elephant.jpg',
                 'php-elephant-inline',
                 'image/jpeg'
             );
    }
    
}
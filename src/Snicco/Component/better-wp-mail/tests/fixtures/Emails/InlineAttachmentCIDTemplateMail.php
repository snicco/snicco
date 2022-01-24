<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

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
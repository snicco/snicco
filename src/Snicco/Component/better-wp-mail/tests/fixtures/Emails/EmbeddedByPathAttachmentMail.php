<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

final class EmbeddedByPathAttachmentMail extends Email
{
    
    public function configure()
    {
        $this->subject('foo')->text('öö')
             ->html('<h1>ÜÜ</h1>')
             ->embedFromPath(
                 dirname(__DIR__, 2).'/fixtures/php-elephant.jpg',
                 'my-elephant',
                 'image/jpeg'
             );
    }
    
}
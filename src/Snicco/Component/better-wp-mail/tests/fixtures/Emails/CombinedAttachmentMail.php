<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

final class CombinedAttachmentMail extends Email
{
    
    public function configure()
    {
        $string = file_get_contents(dirname(__DIR__, 2).'/fixtures/php-elephant.jpg');
        
        $this->subject('foo')->text('öö')
             ->html('<h1>ÜÜ</h1>')
             ->embed($string, 'php-elephant-inline', 'image/jpeg')
             ->attachFromPath(
                 dirname(__DIR__, 2).'/fixtures/php-elephant.jpg',
                 'php-elephant-attachment',
                 'image/jpeg'
             );
    }
    
}
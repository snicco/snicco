<?php

declare(strict_types=1);

namespace Tests\integration\Testing;

use Tests\FrameworkTestCase;
use Snicco\Mail\MailBuilder;

final class InteractsWithMailTest extends FrameworkTestCase
{
    
    /** @test */
    public function test_mails_can_be_faked()
    {
        $this->bootApp();
        
        $this->fakeMails();
        
        $mail = $this->app[MailBuilder::class];
    }
    
}
<?php

declare(strict_types=1);

namespace Tests\BetterWPMailBundle;

use Snicco\MailBundle\MailServiceProvider;
use Snicco\ViewBundle\ViewServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Bundle\Testing\Concerns\InteractsWithMail;

class MailBundleTestCase extends FrameworkTestCase
{
    
    use InteractsWithMail;
    
    protected function packageProviders() :array
    {
        return [
            MailServiceProvider::class,
            ViewServiceProvider::class,
        ];
    }
    
}
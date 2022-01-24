<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;

trait InteractsWithMail
{
    
    /**
     * @var Transport|FakeTransport
     */
    protected Transport $fake_mailer;
    
    protected function withFakeMailer()
    {
        $this->afterApplicationBooted(function () {
            $this->fake_mailer = $this->app[Transport::class];
        });
    }
    
}
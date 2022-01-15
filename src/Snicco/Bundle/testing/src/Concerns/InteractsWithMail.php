<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Testing\FakeMailer;

trait InteractsWithMail
{
    
    /**
     * @var Mailer|FakeMailer
     */
    protected Mailer $fake_mailer;
    
    protected function withFakeMailer()
    {
        $this->afterApplicationBooted(function () {
            $this->fake_mailer = $this->app[Mailer::class];
        });
    }
    
}
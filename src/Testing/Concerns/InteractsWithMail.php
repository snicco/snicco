<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Mail\Testing\FakeMailer;

trait InteractsWithMail
{
    
    protected FakeMailer $fake_mailer;
    
}
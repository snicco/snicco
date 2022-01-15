<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\integration;

use Snicco\Mail\Email;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\Exceptions\MissingContentIdException;

final class MissingCIDExceptionTest extends WPTestCase
{
    
    /** @test */
    public function testExceptionIfNoCIDExistsForFileName()
    {
        $email = new MissingCIDEmail();
        $email->configure();
        
        $cid = $email->getCid('php-elephant-1');
        
        $this->expectException(MissingContentIdException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The mailable [%s] has no embedded attachment with the name: [%s].',
                MissingCIDEmail::class,
                'php-elephant-2'
            )
        );
        
        $cid = $email->getCid('php-elephant-2');
    }
    
}

class MissingCIDEmail extends Email
{
    
    public function configure()
    {
        $this->subject('foo')
             ->html('<h1>Bar</h1>')->embedFromPath(
                dirname(__DIR__).'/fixtures/php-elephant.jpg',
                'php-elephant-1'
            );
    }
    
}
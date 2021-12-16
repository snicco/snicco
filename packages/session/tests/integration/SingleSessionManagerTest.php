<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Codeception\TestCase\WPTestCase;
use Snicco\Session\ValueObjects\CookiePool;
use Snicco\Session\SingleSessionSessionManager;
use Tests\Codeception\shared\helpers\SessionHelpers;

final class SingleSessionManagerTest extends WPTestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function multiple_calls_to_get_session_return_the_same_instance()
    {
        $manager = new SingleSessionSessionManager($this->getSessionManager());
        
        $session1 = $manager->start(CookiePool::fromSuperGlobals());
        $session2 = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertSame($session1, $session2);
    }
    
}
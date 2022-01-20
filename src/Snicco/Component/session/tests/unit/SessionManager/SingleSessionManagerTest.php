<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\unit\SessionManager;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;
use Snicco\Component\Session\SessionManager\SingleSessionSessionManager;

final class SingleSessionManagerTest extends TestCase
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
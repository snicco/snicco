<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;
use Snicco\Component\Session\ValueObject\ReadOnlySession;

final class ReadOnlySessionTest extends TestCase
{

    use SessionHelpers;

    /** @test */
    public function testImmutableStore()
    {
        $session = $this->newSession();

        $store = ReadOnlySession::fromSession($session);

        $this->assertInstanceOf(ImmutableSession::class, $store);
        $this->assertNotInstanceOf(Session::class, $store);
        $this->assertNotInstanceOf(MutableSession::class, $store);
    }

}
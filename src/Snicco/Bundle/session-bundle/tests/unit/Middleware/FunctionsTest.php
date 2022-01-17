<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use DateTimeImmutable;
use Snicco\Session\Session;
use Snicco\SessionBundle\Keys;
use Snicco\Session\ImmutableSession;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Session\Contracts\ImmutableSessionInterface;
use Snicco\Component\HttpRouting\Tests\helpers\CreatePsr17Factories;

use function Snicco\SessionBundle\getReadSession;
use function Snicco\SessionBundle\getWriteSession;

final class FunctionsTest extends UnitTest
{
    
    use CreatePsr17Factories;
    
    /**
     * @var Request
     */
    private $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->request = new Request (
            $this->psrServerRequestFactory()->createServerRequest('GET', '/foo')
        );
    }
    
    /** @test */
    public function testReadSessionExtractsSession()
    {
        $request = $this->request->withAttribute(
            Keys::READ_SESSION,
            ImmutableSession::fromSession(
                new Session(SessionId::createFresh(), [], new DateTimeImmutable())
            )
        );
        
        $session = getReadSession($request);
        
        $this->assertInstanceOf(ImmutableSessionInterface::class, $session);
    }
    
    /** @test */
    public function testReadSessionThrowsException()
    {
        $this->expectExceptionMessage("No read-only session has been shared with the request.");
        
        getReadSession($this->request);
    }
    
    /** @test */
    public function testWriteSessionExtractsSession()
    {
        $request = $this->request->withAttribute(
            Keys::WRITE_SESSION,
            new Session(SessionId::createFresh(), [], new DateTimeImmutable())
        );
        
        $session = getWriteSession($request);
        
        $this->assertInstanceOf(SessionInterface::class, $session);
    }
    
    /** @test */
    public function testWriteSessionThrowsException()
    {
        $request = $this->request->withAttribute(
            Keys::WRITE_SESSION,
            'foo'
        );
        
        $this->expectExceptionMessage('No writable session has been shared with the request.');
        getWriteSession($request);
    }
    
}
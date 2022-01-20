<?php

declare(strict_types=1);

namespace Tests\SessionBundle\unit\Middleware;

use DateTimeImmutable;
use Snicco\SessionBundle\Keys;
use Snicco\Component\Session\SessionInterface;
use Tests\Codeception\shared\UnitTest;
use Snicco\Component\Session\ValueObject\ReadOnly;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Session\ValueObject\SessionId;
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
            ReadOnly::fromSession(
                new SessionInterface(SessionId::createFresh(), [], new DateTimeImmutable())
            )
        );
        
        $session = getReadSession($request);
        
        $this->assertInstanceOf(ReadOnly::class, $session);
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
            new SessionInterface(SessionId::createFresh(), [], new DateTimeImmutable())
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
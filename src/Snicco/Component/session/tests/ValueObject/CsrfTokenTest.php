<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\CsrfToken;

final class CsrfTokenTest extends TestCase
{

    /**
     * @test
     */
    public function testAsString(): void
    {
        $csrf_token = new CsrfToken('foobar');
        $this->assertSame('foobar', $csrf_token->asString());
    }

    /**
     * @test
     */
    public function testAsQueryParameter(): void
    {
        $csrf_token = new CsrfToken('foobar');

        $this->assertSame('_token=foobar', $csrf_token->asQueryParameter());
    }

    /**
     * @test
     */
    public function testAsInputField(): void
    {
        $csrf_token = new CsrfToken('foobar');

        $this->assertSame(
            '<input type="hidden" name="_token" value="foobar">',
            $csrf_token->asInputField()
        );
    }

    /**
     * @test
     */
    public function testAsMetaProperty(): void
    {
        $csrf_token = new CsrfToken('foobar');

        $this->assertSame('<meta name="_token" content="foobar">', $csrf_token->asMetaProperty());
    }

}
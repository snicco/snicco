<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\Functional;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Testing\Functional\AssertableDOM;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
final class AssertableDOMTest extends TestCase
{
    /**
     * @test
     */
    public function test_assert_selector_exists(): void
    {
        $dom = new AssertableDOM(new Crawler('<html><body><h1>'));
        $dom->assertSelectorExists('body > h1');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('matches selector "body > h1".');

        (new AssertableDOM((new Crawler('<html><head><title>Foo'))))->assertSelectorExists('body > h1');
    }

    /**
     * @test
     */
    public function test_assert_selector_not_exists(): void
    {
        $this->getDOM(new Crawler('<html><head><title>Foo'))
            ->assertSelectorNotExists('body > h1');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('does not match selector "body > h1".');
        $this->getDOM(new Crawler('<html><body><h1>'))
            ->assertSelectorNotExists('body > h1');
    }

    /**
     * @test
     */
    public function test_assert_selector_text_not_contains(): void
    {
        $this->getDOM(new Crawler('<html><body><h1>Foo'))
            ->assertSelectorTextNotContains('body > h1', 'Bar');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "body > h1" and the text "Foo" of the node matching selector "body > h1" does not contain "Foo".'
        );
        $this->getDOM(new Crawler('<html><body><h1>Foo'))
            ->assertSelectorTextNotContains('body > h1', 'Foo');
    }

    /**
     * @test
     */
    public function test_assert_page_title_same(): void
    {
        $this->getDOM(new Crawler('<html><head><title>Foo'))
            ->assertPageTitleSame('Foo');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "title" and has a node matching selector "title" with content "Bar".'
        );
        $this->getDOM(new Crawler('<html><head><title>Foo'))
            ->assertPageTitleSame('Bar');
    }

    /**
     * @test
     */
    public function test_assert_page_title_contains(): void
    {
        $this->getDOM(new Crawler('<html><head><title>Foobar'))
            ->assertPageTitleContains('Foo');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "title" and the text "Foo" of the node matching selector "title" contains "Bar".'
        );
        $this->getDOM(new Crawler('<html><head><title>Foo'))
            ->assertPageTitleContains('Bar');
    }

    /**
     * @test
     */
    public function test_assert_input_value_same(): void
    {
        $this->getDOM(
            new Crawler('<html><body><form><input type="text" name="username" value="Fabien">')
        )->assertInputValueSame('username', 'Fabien');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "input[name="password"]" and has a node matching selector "input[name="password"]" with attribute "value" of value "pa$$".'
        );
        $this->getDOM(new Crawler('<html><head><title>Foo'))
            ->assertInputValueSame('password', 'pa$$');
    }

    /**
     * @test
     */
    public function test_assert_input_value_not_same(): void
    {
        $this->getDOM(
            new Crawler('<html><body><input type="text" name="username" value="Helene">')
        )->assertInputValueNotSame('username', 'Fabien');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "input[name="password"]" and does not have a node matching selector "input[name="password"]" with attribute "value" of value "pa$$".'
        );
        $this->getDOM(
            new Crawler('<html><body><form><input type="text" name="password" value="pa$$">')
        )->assertInputValueNotSame('password', 'pa$$');
    }

    /**
     * @test
     */
    public function test_assert_checkbox_checked(): void
    {
        $this->getDOM(
            new Crawler('<html><body><form><input type="checkbox" name="rememberMe" checked>')
        )->assertCheckboxChecked('rememberMe');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "input[name="rememberMe"]" and has a node matching selector "input[name="rememberMe"]" with attribute "checked" of value "checked".'
        );
        $this->getDOM(
            new Crawler('<html><body><form><input type="checkbox" name="rememberMe">')
        )->assertCheckboxChecked('rememberMe');
    }

    /**
     * @test
     */
    public function test_assert_checkbox_not_checked(): void
    {
        $this->getDOM(
            new Crawler('<html><body><form><input type="checkbox" name="rememberMe">')
        )->assertCheckboxNotChecked('rememberMe');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'matches selector "input[name="rememberMe"]" and does not have a node matching selector "input[name="rememberMe"]" with attribute "checked" of value "checked".'
        );
        $this->getDOM(
            new Crawler('<html><body><form><input type="checkbox" name="rememberMe" checked>')
        )->assertCheckboxNotChecked('rememberMe');
    }

    /**
     * @test
     */
    public function test_assert_form_value(): void
    {
        $this->getDOM(
            new Crawler(
                '<html><body><form id="form"><input type="text" name="username" value="Fabien">',
                'http://localhost'
            )
        )->assertFormValue('#form', 'username', 'Fabien');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that two strings are identical.');
        $this->getDOM(
            new Crawler(
                '<html><body><form id="form"><input type="text" name="username" value="Fabien">',
                'http://localhost'
            )
        )->assertFormValue('#form', 'username', 'Jane');
    }

    /**
     * @test
     */
    public function test_assert_no_form_value(): void
    {
        $this->getDOM(
            new Crawler('<html><body><form id="form"><input type="checkbox" name="rememberMe">', 'http://localhost')
        )->assertNoFormValue('#form', 'rememberMe');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Field "rememberMe" has a value in form "#form".');
        $this->getDOM(
            new Crawler(
                '<html><body><form id="form"><input type="checkbox" name="rememberMe" checked>',
                'http://localhost'
            )
        )->assertNoFormValue('#form', 'rememberMe');
    }

    private function getDOM(Crawler $crawler): AssertableDOM
    {
        return new AssertableDOM($crawler);
    }
}

<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional;

use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorAttributeValueSame;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorExists;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorTextContains;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorTextSame;

use function sprintf;

final class AssertableDOM
{
    private Crawler $crawler;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function assertSelectorExists(string $selector, string $message = ''): void
    {
        PHPUnit::assertThat($this->crawler, new CrawlerSelectorExists($selector), $message);
    }

    public function assertSelectorNotExists(string $selector, string $message = ''): void
    {
        PHPUnit::assertThat($this->crawler, new LogicalNot(new CrawlerSelectorExists($selector)), $message);
    }

    public function assertSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists($selector),
                new CrawlerSelectorTextContains($selector, $text)
            ),
            $message
        );
    }

    public function assertSelectorTextSame(string $selector, string $text, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists($selector),
                new CrawlerSelectorTextSame($selector, $text)
            ),
            $message
        );
    }

    public function assertSelectorTextNotContains(string $selector, string $text, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists($selector),
                new LogicalNot(new CrawlerSelectorTextContains($selector, $text))
            ),
            $message
        );
    }

    public function assertPageTitleSame(string $expectedTitle, string $message = ''): void
    {
        $this->assertSelectorTextSame('title', $expectedTitle, $message);
    }

    public function assertPageTitleContains(string $expectedTitle, string $message = ''): void
    {
        $this->assertSelectorTextContains('title', $expectedTitle, $message);
    }

    public function assertInputValueSame(string $fieldName, string $expectedValue, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists(sprintf('input[name="%s"]', $fieldName)),
                new CrawlerSelectorAttributeValueSame(sprintf('input[name="%s"]', $fieldName), 'value', $expectedValue)
            ),
            $message
        );
    }

    public function assertInputValueNotSame(string $fieldName, string $expectedValue, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists(sprintf('input[name="%s"]', $fieldName)),
                new LogicalNot(
                    new CrawlerSelectorAttributeValueSame(sprintf(
                        'input[name="%s"]',
                        $fieldName
                    ), 'value', $expectedValue)
                )
            ),
            $message
        );
    }

    public function assertCheckboxChecked(string $fieldName, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists(sprintf('input[name="%s"]', $fieldName)),
                new CrawlerSelectorAttributeValueSame(sprintf('input[name="%s"]', $fieldName), 'checked', 'checked')
            ),
            $message
        );
    }

    public function assertCheckboxNotChecked(string $fieldName, string $message = ''): void
    {
        PHPUnit::assertThat(
            $this->crawler,
            LogicalAnd::fromConstraints(
                new CrawlerSelectorExists(sprintf('input[name="%s"]', $fieldName)),
                new LogicalNot(
                    new CrawlerSelectorAttributeValueSame(sprintf('input[name="%s"]', $fieldName), 'checked', 'checked')
                )
            ),
            $message
        );
    }

    public function assertFormValue(
        string $formSelector,
        string $fieldName,
        string $value,
        string $message = ''
    ): void {
        $node = $this->crawler->filter($formSelector);
        PHPUnit::assertNotEmpty($node, sprintf('Form "%s" not found.', $formSelector));
        $values = $node->form()
            ->getValues();
        PHPUnit::assertArrayHasKey(
            $fieldName,
            $values,
            $message ?: sprintf('Field "%s" not found in form "%s".', $fieldName, $formSelector)
        );
        PHPUnit::assertSame($value, $values[$fieldName]);
    }

    public function assertNoFormValue(string $formSelector, string $fieldName, string $message = ''): void
    {
        $node = $this->crawler->filter($formSelector);
        PHPUnit::assertNotEmpty($node, sprintf('Form "%s" not found.', $formSelector));
        $values = $node->form()
            ->getValues();
        PHPUnit::assertArrayNotHasKey(
            $fieldName,
            $values,
            $message ?: sprintf('Field "%s" has a value in form "%s".', $fieldName, $formSelector)
        );
    }
}

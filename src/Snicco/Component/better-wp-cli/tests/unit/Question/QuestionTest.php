<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Question;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Question\Question;

use function strtoupper;

/**
 * @internal
 */
final class QuestionTest extends TestCase
{
    /**
     * @test
     */
    public function with_default_attributes(): void
    {
        $question = new Question('foo', 'bar');
        $this->assertSame('foo', $question->question());
        $this->assertSame('bar', $question->default());
        $this->assertFalse($question->isHidden());
        $this->assertFalse($question->allowsFallbackToVisibleInput());
        $this->assertNull($question->attempts());

        $question = new Question('foo', 'bar');
        $this->assertSame('', $question->normalize(''));
        $question->validate('');
    }

    /**
     * @test
     */
    public function with_custom_attributes(): void
    {
        $question = new Question(
            'foo',
            'bar',
            function (): void {
                throw new RuntimeException('no');
            },
            1,
            fn (string $value): string => strtoupper($value),
            true,
            false
        );
        $this->assertSame('foo', $question->question());
        $this->assertSame('bar', $question->default());
        $this->assertTrue($question->isHidden());
        $this->assertFalse($question->allowsFallbackToVisibleInput());
        $this->assertSame(1, $question->attempts());

        $this->assertSame('BAZ', $question->normalize('baz'));
        $this->expectExceptionMessage('no');
        $question->validate('foo');
    }

    /**
     * @test
     */
    public function test_is_immutable(): void
    {
        $question1 = new Question('foo', 'bar');
        $this->assertFalse($question1->isHidden());

        $question2 = $question1->withHiddenInput();
        $this->assertFalse($question1->isHidden());
        $this->assertTrue($question2->isHidden());
        $this->assertFalse($question2->allowsFallbackToVisibleInput());

        $question3 = $question2->withFallbackVisibleInput();
        $this->assertTrue($question2->isHidden());
        $this->assertFalse($question2->allowsFallbackToVisibleInput());
        $this->assertTrue($question3->isHidden());
        $this->assertTrue($question3->allowsFallbackToVisibleInput());
    }
}

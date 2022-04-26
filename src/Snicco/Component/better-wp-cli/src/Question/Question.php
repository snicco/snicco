<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Question;

final class Question
{
    private bool $hidden;

    private bool $allow_visible_fallback;

    private string $question;

    private string $default;

    /**
     * @var pure-callable(string):void
     */
    private $validator;

    /**
     * @var pure-callable(string):string
     */
    private $normalizer;

    private ?int $attempts;

    /**
     * @param  null|pure-callable(string):void  $validator
     * @param  null|pure-callable(string):string  $normalizer
     */
    public function __construct(
        string $question,
        string $default = '',
        callable $validator = null,
        int $attempts = null,
        callable $normalizer = null,
        bool $hidden = false,
        bool $allow_visible_fallback = false
    ) {
        $this->question = $question;
        $this->default = $default;
        $this->normalizer = $normalizer ?: fn (string $value): string => $value;
        $this->validator = $validator ?: function (): void {
        };
        $this->attempts = $attempts;
        $this->hidden = $hidden;
        $this->allow_visible_fallback = $allow_visible_fallback;
    }

    public function question(): string
    {
        return $this->question;
    }

    public function default(): string
    {
        return $this->default;
    }

    public function validate(string $answer): void
    {
        ($this->validator)($answer);
    }

    public function normalize(string $answer): string
    {
        return ($this->normalizer)($answer);
    }

    public function attempts(): ?int
    {
        return $this->attempts;
    }

    public function withHiddenInput(bool $hidden = true): self
    {
        $new = clone $this;
        $new->hidden = $hidden;

        return $new;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function allowsFallbackToVisibleInput(): bool
    {
        return $this->allow_visible_fallback;
    }

    public function withFallbackVisibleInput(bool $allow_visible = true): self
    {
        $new = clone $this;
        $new->allow_visible_fallback = $allow_visible;

        return $new;
    }
}

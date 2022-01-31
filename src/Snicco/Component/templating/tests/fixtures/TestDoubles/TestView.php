<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests\fixtures\TestDoubles;

use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\View\View;

class TestView implements View
{

    private array $context = [];
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function with($key, $value = null): View
    {
        if (is_array($key)) {
            $this->context = array_merge($this->context(), $key);
        } else {
            $this->context[$key] = $value;
        }

        return $this;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function toString(): string
    {
        $context = '[';

        foreach ($this->context as $key => $value) {
            if ($key === '__view') {
                continue;
            }
            $context .= $key . '=>' . $value . ',';
        }
        $context = rtrim($context, ',');
        $context .= ']';

        return 'VIEW:' . $this->name . ',CONTEXT:' . $context;
    }

    public function path(): string
    {
    }

    public function parent(): ?PHPView
    {
    }

    public function name(): string
    {
        return $this->name;
    }

}
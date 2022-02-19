<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use Closure;
use Snicco\Component\ParameterBag\ParameterBag;

use function call_user_func;

final class GlobalViewContext
{

    /**
     * @var array<string,mixed>
     */
    private array $context = [];

    /**
     * @param mixed $context
     */
    public function add(string $name, $context): void
    {
        if (is_array($context)) {
            $context = new ParameterBag($context);
        }

        $this->context[$name] = $context;
    }

    /**
     * @interal
     *
     * @return array<string,mixed>
     *
     * @psalm-suppress MissingClosureReturnType
     */
    public function get(): array
    {
        return array_map(function ($context) {
            return ($context instanceof Closure)
                ? call_user_func($context)
                : $context;
        }, $this->context);
    }

}
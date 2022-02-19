<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use ReturnTypeWillChange;
use Snicco\Component\StrArr\Arr;

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
            $context = $this->getArrayAccess($context);
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

    private function getArrayAccess(array $context): ArrayAccess
    {
        return new class($context) implements ArrayAccess {

            private array $context;

            public function __construct(array $context)
            {
                $this->context = $context;
            }

            /**
             * @param mixed $offset
             */
            public function offsetExists($offset): bool
            {
                return Arr::has($this->context, (string)$offset);
            }

            /**
             * @param mixed $offset
             * @return mixed
             */
            public function offsetGet($offset)
            {
                return Arr::get($this->context, (string)$offset);
            }

            /**
             * @param mixed $offset
             * @param mixed $value
             */
            #[ReturnTypeWillChange]
            public function offsetSet($offset, $value): void
            {
                throw new BadMethodCallException(
                    'offsetSet not allowed. Global view context is immutable in view.'
                );
            }

            /**
             * @param mixed $offset
             */
            public function offsetUnset($offset): void
            {
                throw new BadMethodCallException(
                    'offsetUnset not allowed. Global view context is immutable in view.'
                );
            }
        };
    }

}
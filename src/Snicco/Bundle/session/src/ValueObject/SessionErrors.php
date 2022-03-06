<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\ValueObject;

use InvalidArgumentException;

use function array_merge;
use function count;
use function is_string;

/**
 * @psalm-immutable
 */
final class SessionErrors
{

    /**
     * @var array<string,array<string,string[]>>
     */
    private array $errors = [];

    public function __construct(array $errors)
    {
        /**
         * @var array $keys
         */
        foreach ($errors as $namespace => $keys) {
            if (!is_string($namespace)) {
                throw new InvalidArgumentException('$errors must be an array keyed by string namespaces.');
            }
            /**
             * @var array $messages
             */
            foreach ($keys as $key => $messages) {
                if (!is_string($key)) {
                    throw new InvalidArgumentException('Each error namespace must be an array with string keys.');
                }
                /**
                 * @var mixed $message
                 */
                foreach ($messages as $message) {
                    if (!is_string($message)) {
                        throw new InvalidArgumentException('All error messages must be strings.');
                    }
                    $this->errors[$namespace][$key][] = $message;
                }
            }
        }
    }

    public function hasKey(string $key, string $namespace = 'default'): bool
    {
        $messages = $this->errors[$namespace][$key] ?? [];
        return count($messages) > 0;
    }

    /**
     * @return string[]
     */
    public function get(string $key, string $namespace = 'default'): array
    {
        return $this->errors[$namespace][$key] ?? [];
    }

    /**
     * @return string[]
     */
    public function all(string $namespace = 'default'): array
    {
        $all_messages = [];

        foreach ($this->errors[$namespace] ?? [] as $messages) {
            $all_messages = array_merge($all_messages, $messages);
        }
        return $all_messages;
    }

    public function count(string $namespace = 'default'): int
    {
        return count($this->all($namespace));
    }

}
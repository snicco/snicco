<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use ArrayAccess;
use BadMethodCallException;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\Exception\BadConfigType;
use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\StrArr\Arr;

use function gettype;
use function is_bool;
use function is_int;

/**
 * @api
 */
final class ReadOnlyConfig implements ArrayAccess
{

    private array $items;

    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    public static function fromWritableConfig(WritableConfig $config): ReadOnlyConfig
    {
        return new self($config->toArray());
    }

    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getString(string $key): string
    {
        $val = $this->get($key);
        if (!is_string($val)) {
            throw BadConfigType::forKey($key, 'string', gettype($val));
        }
        return $val;
    }

    /**
     * @return mixed
     * @throws MissingConfigKey
     */
    public function get(string $key)
    {
        if (!Arr::has($this->items, $key)) {
            throw new MissingConfigKey("The key [$key] does not exist in the configuration.");
        }

        return Arr::get($this->items, $key);
    }

    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getInt(string $key): int
    {
        $val = $this->get($key);
        if (!is_int($val)) {
            throw BadConfigType::forKey($key, 'integer', gettype($val));
        }
        return $val;
    }

    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getArray(string $key): array
    {
        $val = $this->get($key);
        if (!is_array($val)) {
            throw BadConfigType::forKey($key, 'array', gettype($val));
        }
        return $val;
    }

    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getBool(string $key): bool
    {
        $val = $this->get($key);
        if (!is_bool($val)) {
            throw BadConfigType::forKey($key, 'boolean', gettype($val));
        }
        return $val;
    }

    public function offsetExists($offset): bool
    {
        return Arr::has($this->items, (string)$offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws MissingConfigKey
     */
    public function offsetGet($offset)
    {
        return $this->get((string)$offset);
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('The configuration is read-only and cannot be changed.');
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('The configuration is read-only and cannot be changed.');
    }

    public function toArray(): array
    {
        return $this->items;
    }

}
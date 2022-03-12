<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\StrArr\Arr;

final class ReadOnlyConfig extends Config
{
    private array $items;

    /**
     * @param mixed[] $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @param mixed $default
     *
     * @throws MissingConfigKey
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (! Arr::has($this->items, $key)) {
            throw new MissingConfigKey(sprintf('The key [%s] does not exist in the configuration.', $key));
        }

        return Arr::get($this->items, $key);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}

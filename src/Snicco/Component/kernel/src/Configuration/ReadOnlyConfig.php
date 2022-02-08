<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\StrArr\Arr;

/**
 * @api
 */
final class ReadOnlyConfig extends Config
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
     * @param mixed $default
     * @return mixed
     * @throws MissingConfigKey
     */
    public function get(string $key, $default = null)
    {
        if (!Arr::has($this->items, $key)) {
            throw new MissingConfigKey("The key [$key] does not exist in the configuration.");
        }

        return Arr::get($this->items, $key);
    }

    public function toArray(): array
    {
        return $this->items;
    }

}
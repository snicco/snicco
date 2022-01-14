<?php

declare(strict_types=1);

namespace Snicco\Core\Configuration;

use Snicco\StrArr\Arr;
use Snicco\Core\Exception\BadConfigType;
use Snicco\Core\Exception\MissingConfigKey;

/**
 * @api
 */
final class ReadOnlyConfig
{
    
    private array $items;
    
    private function __construct(array $items)
    {
        $this->items = $items;
    }
    
    public static function fromArray(array $items) :self
    {
        return new self($items);
    }
    
    /**
     * @return mixed
     * @throws MissingConfigKey
     */
    public function get(string $key)
    {
        if ( ! Arr::has($this->items, $key)) {
            throw new MissingConfigKey("The key [$key] does not exist in the configuration.");
        }
        
        return Arr::get($this->items, $key);
    }
    
    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getString(string $key) :string
    {
        $val = $this->get($key);
        if ( ! is_string($val)) {
            throw BadConfigType::forKey($key, 'string', gettype($val));
        }
        return $val;
    }
    
    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getInteger(string $key) :int
    {
        $val = $this->get($key);
        if ( ! is_int($val)) {
            throw BadConfigType::forKey($key, 'integer', gettype($val));
        }
        return $val;
    }
    
    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function getArray(string $key) :array
    {
        $val = $this->get($key);
        if ( ! is_array($val)) {
            throw BadConfigType::forKey($key, 'array', gettype($val));
        }
        return $val;
    }
    
}
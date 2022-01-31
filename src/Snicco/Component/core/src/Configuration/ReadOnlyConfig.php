<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Configuration;

use ArrayAccess;
use LogicException;
use ReturnTypeWillChange;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\Core\Exception\BadConfigType;
use Snicco\Component\Core\Exception\MissingConfigKey;

use function is_int;
use function gettype;
use function is_bool;

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
    
    public static function fromArray(array $items) :self
    {
        return new self($items);
    }
    
    public static function fromWritableConfig(WritableConfig $config) :ReadOnlyConfig
    {
        return new self($config->toArray());
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
    public function string(string $key) :string
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
    public function integer(string $key) :int
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
    public function array(string $key) :array
    {
        $val = $this->get($key);
        if ( ! is_array($val)) {
            throw BadConfigType::forKey($key, 'array', gettype($val));
        }
        return $val;
    }
    
    /**
     * @throws BadConfigType
     * @throws MissingConfigKey
     */
    public function boolean(string $key) :bool
    {
        $val = $this->get($key);
        if ( ! is_bool($val)) {
            throw BadConfigType::forKey($key, 'boolean', gettype($val));
        }
        return $val;
    }
    
    public function offsetExists($offset) :bool
    {
        return Arr::has($this->items, $offset);
    }
    
    /**
     * @return mixed
     * @throws MissingConfigKey
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new LogicException("The configuration is read-only and cannot be changed.");
    }
    
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new LogicException('The configuration is read-only and cannot be changed.');
    }
    
    public function toArray() :array
    {
        return $this->items;
    }
    
}
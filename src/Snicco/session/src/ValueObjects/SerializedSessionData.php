<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

use function is_array;
use function serialize;
use function unserialize;

/**
 * @interal
 */
final class SerializedSessionData
{
    
    /**
     * @var string
     */
    private $as_string;
    
    /**
     * @var DateTimeImmutable
     */
    private $last_activity;
    
    /**
     * @param  string|array  $data
     */
    private function __construct(string $data, DateTimeImmutable $last_activity)
    {
        $this->as_string = $data;
        $this->last_activity = $last_activity;
    }
    
    public static function fromSerializedString(string $string, int $last_activity_as_timestamp) :SerializedSessionData
    {
        if ( ! self::isSerializedString($string)) {
            throw new InvalidArgumentException("$string is not a valid serialized string.");
        }
        
        return new self(
            $string,
            (new DateTimeImmutable())->setTimestamp($last_activity_as_timestamp)
        );
    }
    
    public static function fromArray(array $data, int $last_activity_as_timestamp) :SerializedSessionData
    {
        return new self(
            serialize($data),
            (new DateTimeImmutable())->setTimestamp($last_activity_as_timestamp)
        );
    }
    
    private static function isSerializedString(string $data) :bool
    {
        return @unserialize($data) !== false;
    }
    
    public function lastActivity() :DateTimeImmutable
    {
        return $this->last_activity;
    }
    
    public function asString() :string
    {
        return $this->as_string;
    }
    
    public function __toString()
    {
        return $this->asString();
    }
    
    public function asArray() :array
    {
        $data = unserialize($this->as_string);
        if ( ! is_array($data)) {
            $data = [];
        }
        
        return $data;
    }
    
}
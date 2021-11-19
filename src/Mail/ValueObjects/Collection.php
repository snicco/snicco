<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
abstract class Collection
{
    
    /**
     * @var array
     */
    private $names;
    
    public function __construct(Name ...$names)
    {
        $this->names = $names;
    }
    
    public function getValid() :self
    {
        return new static(
            ...array_filter(
                array_map(function (Name $name) {
                    if ( ! $name->valid()) {
                        return null;
                    }
                    return $name;
                }, $this->names)
            )
        );
    }
    
    /**
     * @return array<string>
     */
    public function format() :array
    {
        return array_filter(
            array_map(function (Name $name) {
                return $name->formatted();
            }, $this->names)
        );
    }
    
    /**
     * @return Name[]
     */
    public function toArray() :array
    {
        return $this->names;
    }
    
}
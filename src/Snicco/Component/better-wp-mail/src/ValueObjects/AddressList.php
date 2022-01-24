<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use Countable;
use ArrayIterator;
use IteratorAggregate;

use function count;
use function is_string;
use function array_values;

final class AddressList implements Countable, IteratorAggregate
{
    
    /**
     * @var array<string,Address>
     */
    private array $addresses = [];
    
    public function __construct(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }
    
    /**
     * @param  Address[]|AddressList  $list
     *
     * @return AddressList
     */
    public function merge($list) :AddressList
    {
        $new = clone $this;
        
        foreach ($list as $address) {
            $new->addAddress($address);
        }
        return $new;
    }
    
    public function count() :int
    {
        return count($this->addresses);
    }
    
    /**
     * @return ArrayIterator|Address[]
     */
    public function getIterator() :ArrayIterator
    {
        return new ArrayIterator(array_values($this->addresses));
    }
    
    /**
     * @param  string|Address  $address
     *
     * @return bool
     */
    public function has($address) :bool
    {
        $address = is_string($address) ? Address::create($address) : $address;
        
        return isset($this->addresses[$address->getAddress()]);
    }
    
    private function addAddress(Address $address)
    {
        $email = $address->getAddress();
        
        if (isset($this->addresses[$email])) {
            return;
        }
        
        $this->addresses[$email] = $address;
    }
    
}
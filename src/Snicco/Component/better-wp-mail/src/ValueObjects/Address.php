<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use WP_User;
use InvalidArgumentException;

use function sprintf;
use function gettype;
use function filter_var;

use const FILTER_VALIDATE_EMAIL;

/**
 * Represents an "email mailbox". By default, the domain part is validated using the filter_var.
 * For better validation it is recommended to use
 * https://github.com/egulias/EmailValidator
 *
 * @api
 */
final class Address
{
    
    // @see https://regexr.com/69uh5
    private const PATTERN = '/^(?<name>\w+\s?\w+)?\s*<(?<address>[^>]+)>$/';
    
    /**
     * @var callable
     */
    public static  $email_validator;
    private string $address;
    private string $name;
    
    private function __construct(string $address, string $name = '')
    {
        $address = strtolower($address);
        
        if (self::$email_validator === null) {
            self::$email_validator = function (string $email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            };
        }
        
        if ( ! call_user_func(self::$email_validator, $address)) {
            throw new InvalidArgumentException("[$address] is not a valid email.");
        }
        
        $this->address = $address;
        $this->name = ucwords(trim(str_replace(["\n", "\r"], '', $name)));
    }
    
    /**
     * @param  Address|string|WP_User|array<string,string>  $address
     *
     * @throws InvalidArgumentException
     */
    public static function create($address) :Address
    {
        if ($address instanceof Address) {
            return $address;
        }
        
        if (is_string($address)) {
            if (strpos($address, '<') === false) {
                return new self($address);
            }
            
            if ( ! preg_match(self::PATTERN, $address, $matches)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "[%s] is not a valid address",
                        $address
                    )
                );
            }
            
            return new self($matches['address'], $matches['name']);
        }
        
        if (is_array($address)) {
            if (isset($address['name']) && $address['email']) {
                return new self($address['email'], $address['name']);
            }
            return new self(...array_values($address));
        }
        
        if ( ! $address instanceof WP_User) {
            throw new InvalidArgumentException(
                sprintf(
                    '$address has to be string,array or an instance of WP_User. Got [%s].',
                    gettype($address)
                )
            );
        }
        
        if ($address->first_name) {
            $name = $address->first_name.' '.$address->last_name ? : '';
        }
        else {
            $name = $address->display_name ? : '';
        }
        
        return new self($address->user_email, $name);
    }
    
    /**
     * @param  array<array<string,string>>|WP_User[]  $addresses
     *
     * @return Address[]
     * @throws InvalidArgumentException
     */
    public static function createFromArray(array $addresses) :array
    {
        $a = [];
        foreach ($addresses as $address) {
            $a[] = self::create($address);
        }
        
        return $a;
    }
    
    public function getAddress() :string
    {
        return $this->address;
    }
    
    public function getName() :string
    {
        return $this->name;
    }
    
    public function toString() :string
    {
        return ($name = $this->getName())
            ? ($name.' <'.$this->getAddress().'>')
            : $this->getAddress();
    }
    
    public function __toString()
    {
        return $this->toString();
    }
    
}
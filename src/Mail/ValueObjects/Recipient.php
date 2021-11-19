<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/**
 * @api
 */
final class Recipient extends Name
{
    
    /**
     * @var string
     */
    protected $prefix = '';
    
    public function getFirstName() :string
    {
        $result = strstr($this->name, ' ', true);
        
        return $result === false ? $this->name : $result;
    }
    
    public function getLastName() :string
    {
        return array_reverse(explode(' ', $this->name, 2))[0];
    }
    
}
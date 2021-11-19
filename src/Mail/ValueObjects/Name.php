<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

use WP_User;
use ReflectionClass;

/**
 * @api
 */
abstract class Name
{
    
    /**
     * @var string
     */
    protected $email;
    
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var bool
     */
    protected $valid = true;
    
    /**
     * @var string
     */
    protected $prefix;
    
    public function __construct(string $email, string $name = '')
    {
        $name = trim($name);
        $email = strtolower($email);
        
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            trigger_error("[$email] is not a valid email. Recipient name: [$name]", E_USER_WARNING);
            $this->valid = false;
        }
        
        $this->email = $email;
        $this->name = ucwords($name);
        
        if ( ! isset($this->prefix)) {
            $name = (new ReflectionClass($this))->getShortName();
            $this->prefix = ucfirst(strtolower($name));
        }
        
        $this->prefix = trim($this->prefix, ':');
    }
    
    public static function fromWPUser(WP_User $user) :self
    {
        if ($user->first_name) {
            $name = ucwords($user->first_name).' '.ucwords($user->last_name ?? '');
        }
        else {
            $name = $user->display_name;
        }
        
        return new static($user->user_email, $name);
    }
    
    public function valid() :bool
    {
        return $this->valid;
    }
    
    public function getName() :string
    {
        return $this->name;
    }
    
    public function getEmail() :string
    {
        return $this->email;
    }
    
    public function formatted() :string
    {
        $formatted = ( ! empty($this->name)) ? $this->name.' <'.$this->email.'>' : $this->email;
        
        if ( ! empty($this->prefix)) {
            $formatted = "{$this->prefix}: $formatted";
        }
        
        return $formatted;
    }
    
    public function __toString()
    {
        return $this->formatted();
    }
    
}
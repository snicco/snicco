<?php

declare(strict_types=1);

namespace Snicco\Testing\Constraints;

use ReflectionClass;
use PHPUnit\Framework\Constraint\Constraint;

class SeeInOrder extends Constraint
{
    
    /**
     * The string under validation.
     */
    protected string $content;
    
    /**
     * The last value that failed to pass validation.
     */
    protected string $failed_value;
    
    /**
     * Create a new constraint instance.
     *
     * @param  string  $content
     *
     * @return void
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }
    
    /**
     * Determine if the rule passes validation.
     *
     * @param  array  $values
     *
     * @return bool
     */
    public function matches($values) :bool
    {
        $position = 0;
        
        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }
            
            $valuePosition = mb_strpos($this->content, $value, $position);
            
            if ($valuePosition === false || $valuePosition < $position) {
                $this->failed_value = $value;
                
                return false;
            }
            
            $position = $valuePosition + mb_strlen($value);
        }
        
        return true;
    }
    
    /**
     * Get the description of the failure.
     *
     * @param  array  $values
     *
     * @return string
     */
    public function failureDescription($values) :string
    {
        return sprintf(
            'Failed asserting that \'%s\' contains "%s" in specified order.',
            $this->content,
            $this->failed_value
        );
    }
    
    /**
     * Get a string representation of the object.
     *
     * @return string
     */
    public function toString() :string
    {
        return (new ReflectionClass($this))->name;
    }
    
}
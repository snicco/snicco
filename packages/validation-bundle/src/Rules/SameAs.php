<?php

declare(strict_types=1);

namespace Snicco\Validation\Rules;

use Snicco\Support\Arr;
use Respect\Validation\Rules\AbstractRule;

/**
 * @todo tests
 */
class SameAs extends AbstractRule
{
    
    private string $compare_to;
    // This property is needed for the SameAsException to work.
    private string $key;
    
    public function __construct(string $compare_to)
    {
        $this->compare_to = $compare_to;
    }
    
    public function validate($input) :bool
    {
        $this->key = $compare = Arr::get($input, '__mapped_key');
        
        if ( ! isset($input[$this->compare_to]) || ! $compare) {
            return false;
        }
        
        $actual_value = $input[$compare];
        $desired_value = $input[$this->compare_to];
        
        return $actual_value === $desired_value;
    }
    
}
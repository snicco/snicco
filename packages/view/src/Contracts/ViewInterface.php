<?php

declare(strict_types=1);

namespace Snicco\View\Contracts;

/**
 * @api
 */
interface ViewInterface
{
    
    /**
     * Render the view to a string.
     *
     * @return string
     */
    public function toString() :string;
    
    /**
     * Add context values.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     *
     * @return ViewInterface
     */
    public function with($key, $value = null) :ViewInterface;
    
    /**
     * Get context values.
     *
     * @param  string|null  $key
     * @param  mixed|null  $default
     *
     * @return mixed
     */
    public function context(string $key = null, $default = null);
    
    /**
     * @return string The name of the view
     */
    public function name() :string;
    
    /**
     * @return string The full local path of the view
     */
    public function path() :string;
    
}

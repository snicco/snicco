<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\View;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;

/**
 * @api
 */
interface View
{
    
    /**
     * Render the view to a string.
     *
     * @throws ViewCantBeRendered If any kind of error occurs.
     */
    public function toString() :string;
    
    /**
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     */
    public function with($key, $value = null) :View;
    
    public function context() :array;
    
    public function name() :string;
    
    /**
     * @return string The full local path of the view.
     */
    public function path() :string;
    
}

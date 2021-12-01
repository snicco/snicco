<?php

namespace Snicco\ExceptionHandling;

/*
 * Exact copy of the Symfony Fatal Error class
 * https://github.com/symfony/error-handler/blob/5.3/Error/FatalError.php
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * License: https://github.com/symfony/error-handler/blob/5.3/LICENSE
 */

use Error;
use ReflectionProperty;

use function function_exists;

class FatalError extends Error
{
    
    private $error;
    
    /**
     * {@inheritdoc}
     * @param  array  $error  An array as returned by error_get_last()
     */
    public function __construct(string $message, int $code, array $error, int $traceOffset = null, bool $traceArgs = true, array $trace = null)
    {
        parent::__construct($message, $code);
        
        $this->error = $error;
        
        if (null !== $trace) {
            if ( ! $traceArgs) {
                foreach ($trace as &$frame) {
                    unset($frame['args'], $frame['this'], $frame);
                }
            }
        }
        elseif (null !== $traceOffset) {
            if (function_exists('xdebug_get_function_stack')
                && $trace = @xdebug_get_function_stack()) {
                if (0 < $traceOffset) {
                    array_splice($trace, -$traceOffset);
                }
                
                foreach ($trace as &$frame) {
                    if ( ! isset($frame['type'])) {
                        // XDebug pre 2.1.1 doesn't currently set the call type key http://bugs.xdebug.org/view.php?id=695
                        if (isset($frame['class'])) {
                            $frame['type'] = '::';
                        }
                    }
                    elseif ('dynamic' === $frame['type']) {
                        $frame['type'] = '->';
                    }
                    elseif ('static' === $frame['type']) {
                        $frame['type'] = '::';
                    }
                    
                    // XDebug also has a different name for the parameters array
                    if ( ! $traceArgs) {
                        unset($frame['params'], $frame['args']);
                    }
                    elseif (isset($frame['params']) && ! isset($frame['args'])) {
                        $frame['args'] = $frame['params'];
                        unset($frame['params']);
                    }
                }
                
                unset($frame);
                $trace = array_reverse($trace);
            }
            else {
                $trace = [];
            }
        }
        
        foreach ([
            'file' => $error['file'],
            'line' => $error['line'],
            'trace' => $trace,
        ] as $property => $value) {
            if (null !== $value) {
                $refl = new ReflectionProperty(Error::class, $property);
                $refl->setAccessible(true);
                $refl->setValue($this, $value);
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getError() :array
    {
        return $this->error;
    }
    
}
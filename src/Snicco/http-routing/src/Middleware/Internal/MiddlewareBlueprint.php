<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware\Internal;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

use function Snicco\Component\Core\Utils\isInterface;

/**
 * @api
 */
final class MiddlewareBlueprint
{
    
    private string $class;
    private array  $arguments;
    
    public function __construct(string $class, array $arguments = [])
    {
        if ( ! isInterface($class, MiddlewareInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected $class to implement interface [%s]. Got: [%s]',
                    MiddlewareInterface::class,
                    $class
                )
            );
        }
        $this->class = $class;
        $this->arguments = $arguments;
    }
    
    public static function create(string $class, array $arguments = []) :MiddlewareBlueprint
    {
        return new self($class, $arguments);
    }
    
    public function class() :string
    {
        return $this->class;
    }
    
    public function arguments() :array
    {
        return $this->arguments;
    }
    
}
<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Illuminate;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class WPModel extends IlluminateModel
{

    public static string $factory_namespace;

}
<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Exception;

use Psr\Cache\InvalidArgumentException;

final class Psr6InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentException
{
}

<?php

declare(strict_types=1);

/**
 * @var string|null $greet
 */
echo 'Hello ' . (is_string($greet) ? $greet : 'World');

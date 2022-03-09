<?php

declare(strict_types=1);

/** @var string|null $greet */

echo 'Hello ' . (isset($greet) ? (string) $greet : 'World');

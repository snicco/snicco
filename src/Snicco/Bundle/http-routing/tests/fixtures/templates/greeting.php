<?php

declare(strict_types=1);

/** @var string $greet */

echo 'Hello ' . (isset($greet) ? strval($greet) : 'World');
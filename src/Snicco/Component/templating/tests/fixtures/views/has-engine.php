<?php

declare(strict_types=1);

echo 'View has engine: ' . (isset($view) ? get_class($view) : 'no view engine');
